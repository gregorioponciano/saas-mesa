<?php

declare(strict_types=1);

namespace App\Services\EfiBank;

use App\Exceptions\EfiCredentialsNotConfiguredException;
use App\Models\Tenant;
use App\Services\EncryptedCredentialService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\PendingRequest;

class EfiBankClient
{
    private array $config;
    private string $accessToken;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function forSaas(): self
    {
        return new self([
            'client_id' => config('efibank.saas.client_id'),
            'client_secret' => config('efibank.saas.client_secret'),
            'pix_key' => config('efibank.saas.pix_key'),
            'sandbox' => config('efibank.sandbox'),
            'certificate_path' => config('efibank.saas.certificate_path'),
            'key_path' => config('efibank.saas.key_path'),
            'key_password' => config('efibank.saas.key_password'),
        ]);
    }

    public static function forTenant(Tenant $tenant): self
    {
        $efiCredentials = $tenant->efiCredentials;

        if (!$efiCredentials) {
            throw new EfiCredentialsNotConfiguredException();
        }

        $credentials = app(EncryptedCredentialService::class)
            ->decryptTenantCredentials($efiCredentials);

        return new self([
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
            'pix_key' => $credentials['pix_key'],
            'sandbox' => $credentials['account_type'] === 'sandbox',
            'certificate_content' => $credentials['certificate_content'],
            'certificate_path' => $credentials['certificate_path'],
        ]);
    }

    public function getAccessToken(): string
    {
        if (isset($this->accessToken)) {
            return $this->accessToken;
        }

        $cacheKey = 'efi_token_' . md5($this->config['client_id']);

        $this->accessToken = Cache::remember($cacheKey, 3540, function () {
            $response = $this->http()
                ->withBasicAuth($this->config['client_id'], $this->config['client_secret'])
                ->asForm()
                ->post($this->url('oauth'), [
                    'grant_type' => 'client_credentials',
                ]);

            $data = $response->throw()->json();
            return $data['access_token'] ?? throw new \RuntimeException('Failed to get EfiBank access token');
        });

        return $this->accessToken;
    }

    public function pixCreateImmediateCharge(string $txid, array $body, array $options = []): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->withHeader('Content-Type', 'application/json')
            ->put($this->url('pix') . 'cob/' . $txid, $body);

        return $response->throw()->json();
    }

    public function pixGetCharge(string $txid): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->get($this->url('pix') . 'cob/' . $txid);

        return $response->throw()->json();
    }

    public function pixListCharges(array $params = []): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->get($this->url('pix') . 'cob', $params);

        return $response->throw()->json();
    }

    public function createCharge(array $body): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->withHeader('Content-Type', 'application/json')
            ->post($this->url('charges') . 'charge', $body);

        return $response->throw()->json();
    }

    public function getCharge(string $chargeId): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->get($this->url('charges') . 'charge/' . $chargeId);

        return $response->throw()->json();
    }

    public function createPlan(array $body): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->withHeader('Content-Type', 'application/json')
            ->post($this->url('subscriptions') . 'plan', $body);

        return $response->throw()->json();
    }

    public function createSubscription(array $body): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->withHeader('Content-Type', 'application/json')
            ->post($this->url('subscriptions') . 'subscription', $body);

        return $response->throw()->json();
    }

    public function getSubscription(string $subscriptionId): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->get($this->url('subscriptions') . 'subscription/' . $subscriptionId);

        return $response->throw()->json();
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->put($this->url('subscriptions') . 'subscription/' . $subscriptionId . '/cancel');

        return $response->throw()->json();
    }

    private function http(): PendingRequest
    {
        $http = Http::withOptions([
            'curl' => [],
        ]);

        $cert = null;
        $key = null;
        $keyPassword = $this->config['key_password'] ?? null;

        if (!empty($this->config['certificate_content'])) {
            $content = $this->config['certificate_content'];

            if (str_starts_with($content, '-----BEGIN')) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'efi_cert_');
                file_put_contents($tmpPath, $content);
                $cert = $tmpPath;
            } else {
                $certs = [];
                if (!openssl_pkcs12_read($content, $certs, '')) {
                    throw new \RuntimeException('Falha ao ler certificado .p12. Verifique se o arquivo é válido.');
                }

                $tmpCert = tempnam(sys_get_temp_dir(), 'efi_cert_');
                $tmpKey = tempnam(sys_get_temp_dir(), 'efi_key_');
                file_put_contents($tmpCert, $certs['cert'] . "\n" . $certs['pkey']);
                file_put_contents($tmpKey, $certs['pkey']);

                $cert = $tmpCert;
                $key = $tmpKey;
            }
        } elseif (!empty($this->config['certificate_path'])) {
            $cert = $this->config['certificate_path'];

            if (!empty($this->config['key_path'])) {
                $key = $keyPassword
                    ? [$this->config['key_path'], $keyPassword]
                    : $this->config['key_path'];
            }
        }

        $options = [];
        if ($cert) {
            $options['cert'] = $cert;
        }
        if ($key) {
            $options['ssl_key'] = $key;
        }

        if ($options) {
            $http = $http->withOptions($options);
        }

        return $http;
    }

    private function url(string $type): string
    {
        $urls = $this->config['sandbox'] ?? false
            ? config('efibank.sandbox_urls')
            : config('efibank.urls');

        return $urls[$type] ?? '';
    }

    public function pixGetQRCode(int $locId): array
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token)
            ->get($this->url('pix') . 'loc/' . $locId . '/qrcode');

        return $response->throw()->json();
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
