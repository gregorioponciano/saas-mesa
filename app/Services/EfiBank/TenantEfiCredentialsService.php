<?php

declare(strict_types=1);

namespace App\Services\EfiBank;

use App\Exceptions\EfiCredentialsNotConfiguredException;
use App\Models\Tenant;
use App\Models\TenantEfiCredentials;
use App\Services\EncryptedCredentialService;
use Illuminate\Support\Facades\Log;

class TenantEfiCredentialsService
{
    public function __construct(
        private readonly EncryptedCredentialService $encryptionService,
    ) {}

    public function show(Tenant $tenant): array
    {
        $credentials = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();

        if (!$credentials) {
            return [
                'configured' => false,
            ];
        }

        return [
            'configured' => true,
            'account_type' => $credentials->account_type,
            'account_type_display' => $credentials->account_type === 'production' ? 'Produção' : 'Sandbox',
            'is_active' => $credentials->is_active,
            'has_pix_key' => !empty($credentials->pix_key_encrypted),
            'has_certificate' => !empty($credentials->certificate_content_encrypted),
            'client_id_masked' => $this->mask($credentials->decryptClientId()),
            'pix_key_masked' => $credentials->pix_key_encrypted
                ? $this->mask($credentials->decryptPixKey() ?? '')
                : null,
        ];
    }

    public function save(Tenant $tenant, array $data, ?string $certificateContent = null): TenantEfiCredentials
    {
        $encrypted = $this->encryptionService->encryptTenantCredentials([
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'pix_key' => $data['pix_key'] ?? '',
            'certificate_content' => $certificateContent ?? '',
            'cert_password' => $data['cert_password'] ?? null,
        ]);

        return TenantEfiCredentials::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'client_id_encrypted' => $encrypted['client_id_encrypted'],
                'client_secret_encrypted' => $encrypted['client_secret_encrypted'],
                'pix_key_encrypted' => $encrypted['pix_key_encrypted'],
                'certificate_content_encrypted' => $encrypted['certificate_content_encrypted'],
                'cert_password_encrypted' => $encrypted['cert_password_encrypted'],
                'account_type' => $data['account_type'],
                'is_active' => true,
            ]
        );
    }

    public function test(Tenant $tenant): array
    {
        $credentials = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();

        if (!$credentials) {
            throw new EfiCredentialsNotConfiguredException();
        }

        try {
            $client = EfiBankClient::forTenant($tenant);
            $client->getAccessToken();

            return [
                'success' => true,
                'message' => 'Conexão com EfiBank estabelecida com sucesso!',
                'account_type' => $credentials->account_type,
            ];
        } catch (\Throwable $e) {
            Log::error('EfiBank connection test failed', [
                'tenant_id' => $tenant->id,
                'account_type' => $credentials->account_type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Não foi possível conectar. Verifique suas credenciais.',
            ];
        }
    }

    public function mask(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $len = mb_strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return mb_substr($value, 0, 4) . str_repeat('*', $len - 4);
    }
}
