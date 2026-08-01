<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EfiPixService
{
    private string $clientId;
    private string $clientSecret;
    private string $chavePix;
    private string $baseUrl;
    private string $oauthUrl;
    private string $certPath;
    private string $keyPath;
    private string $certPassword;

    public function __construct()
    {
        $this->clientId = (string) (config('efi.client_id') ?? '');
        $this->clientSecret = (string) (config('efi.client_secret') ?? '');
        $this->chavePix = (string) (config('efi.chave_pix') ?? '');
        $this->baseUrl = (string) (config('efi.base_url') ?? '');
        $this->oauthUrl = (string) (config('efi.oauth_url') ?? '');
        $this->certPath = (string) (config('efi.cert_path') ?? '');
        $this->keyPath = (string) (config('efi.key_path') ?? '');
        $this->certPassword = (string) (config('efi.cert_password') ?? '');
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $config = [];
        if ($this->certPath) {
            $config['cert'] = $this->certPath;
        }
        if ($this->keyPath) {
            $config['ssl_key'] = $this->certPassword
                ? [$this->keyPath, $this->certPassword]
                : $this->keyPath;
        }
        return Http::withOptions($config);
    }

    public function getAccessToken(): string
    {
        $token = Cache::get('efi_pix_token');
        if ($token) return $token;

        $response = $this->http()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->oauthUrl, [
                'grant_type' => 'client_credentials',
            ]);

        $data = $response->throw()->json();

        $token = $data['access_token'] ?? null;
        $expires = ($data['expires_in'] ?? 3600) - 60;

        if ($token) {
            Cache::put('efi_pix_token', $token, $expires);
        }

        return $token;
    }

    public function createImmediateCharge(float $amount, string $txid, string $payerName = '', ?string $payerCpf = null): array
    {
        $token = $this->getAccessToken();
        $txid = substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 26);
        $txid = str_pad($txid, 26, '0');
        $cpf = $payerCpf ?: $this->generateValidCpf();

        $body = [
            'calendario' => ['expiracao' => 3600],
            'devedor' => [
                'cpf' => $cpf,
                'nome' => $payerName ?: 'Cliente',
            ],
            'valor' => ['original' => number_format($amount, 2, '.', '')],
            'chave' => $this->chavePix,
            'solicitacaoPagador' => "Pedido {$txid}",
        ];

        $response = $this->http()
            ->withToken($token)
            ->withHeader('Content-Type', 'application/json')
            ->put("{$this->baseUrl}cob/{$txid}", $body);

        $data = $response->throw()->json();

        return $data;
    }

    private function generateValidCpf(): string
    {
        $n = [];
        for ($i = 0; $i < 9; $i++) {
            $n[] = random_int(0, 9);
        }
        $s = 0;
        for ($i = 0; $i < 9; $i++) {
            $s += $n[$i] * (10 - $i);
        }
        $r = ($s % 11 < 2) ? 0 : 11 - ($s % 11);
        $n[] = $r;
        $s = 0;
        for ($i = 0; $i < 10; $i++) {
            $s += $n[$i] * (11 - $i);
        }
        $r = ($s % 11 < 2) ? 0 : 11 - ($s % 11);
        $n[] = $r;
        return implode('', $n);
    }

    public function verifyPayment(string $txid): array
    {
        $token = $this->getAccessToken();
        $txid = substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 26);
        $txid = str_pad($txid, 26, '0');

        $response = $this->http()
            ->withToken($token)
            ->get("{$this->baseUrl}cob/{$txid}");

        return $response->throw()->json();
    }

    public function paymentIsConfirmed(array $chargeData): bool
    {
        return ($chargeData['status'] ?? '') === 'CONCLUIDA';
    }

    public function generateQrCodeImage(string $pixCopiaECola): string
    {
        $qrCode = new QrCode($pixCopiaECola);

        $writer = new PngWriter();

        return base64_encode($writer->write($qrCode)->getString());
    }
}
