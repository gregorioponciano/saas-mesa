<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantEfiCredentials;
use Illuminate\Support\Facades\Log;

class EncryptedCredentialService
{
    private string $key;

    private string $cipher = 'aes-256-gcm';

    public function __construct()
    {
        $this->key = $this->deriveKey(config('app.key'));
    }

    private function deriveKey(string $appKey): string
    {
        $hash = hash_hkdf('sha256', $appKey, 32, 'tenant-credentials-encryption');

        return $hash;
    }

    public function encrypt(string $value): string
    {
        return $this->encryptWith($value, $this->key);
    }

    private function encryptWith(string $value, string $key): string
    {
        $iv = random_bytes(12);
        $tag = '';

        $encrypted = openssl_encrypt(
            $value,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed: '.openssl_error_string());
        }

        $payload = base64_encode($iv.$tag.$encrypted);

        return $payload;
    }

    public function decrypt(string $payload): string
    {
        return $this->decryptWith($payload, $this->key);
    }

    private function decryptWith(string $payload, string $key): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) < 28) {
            throw new \RuntimeException('Invalid encrypted payload format.');
        }

        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $encrypted = substr($decoded, 28);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed: '.openssl_error_string());
        }

        return $decrypted;
    }

    public function encryptTenantCredentials(array $credentials): array
    {
        return [
            'client_id_encrypted' => $this->encrypt($credentials['client_id']),
            'client_secret_encrypted' => $this->encrypt($credentials['client_secret']),
            'pix_key_encrypted' => isset($credentials['pix_key']) ? $this->encrypt($credentials['pix_key']) : null,
            'certificate_content_encrypted' => isset($credentials['certificate_content'])
                ? $this->encrypt($credentials['certificate_content'])
                : null,
        ];
    }

    public function decryptTenantCredentials(TenantEfiCredentials $credentials): array
    {
        try {
            return [
                'client_id' => $this->decrypt($credentials->client_id_encrypted),
                'client_secret' => $this->decrypt($credentials->client_secret_encrypted),
                'pix_key' => $credentials->pix_key_encrypted
                    ? $this->decrypt($credentials->pix_key_encrypted)
                    : null,
                'account_type' => $credentials->account_type,
                'certificate_content' => $credentials->certificate_content_encrypted
                    ? $this->decrypt($credentials->certificate_content_encrypted)
                    : null,
                'certificate_path' => $credentials->certificate_path_encrypted
                    ? $this->decrypt($credentials->certificate_path_encrypted)
                    : null,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to decrypt tenant credentials', [
                'tenant_id' => $credentials->tenant_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function rotateKey(string $oldKey, string $newKey): void
    {
        $oldDerivedKey = $this->deriveKey($oldKey);
        $newDerivedKey = $this->deriveKey($newKey);

        TenantEfiCredentials::chunk(100, function ($credentialsBatch) use ($oldDerivedKey, $newDerivedKey) {
            foreach ($credentialsBatch as $cred) {
                try {
                    $updates = [];

                    foreach ([
                        'client_id_encrypted',
                        'client_secret_encrypted',
                        'pix_key_encrypted',
                        'certificate_path_encrypted',
                        'certificate_content_encrypted',
                        'webhook_secret_encrypted',
                    ] as $field) {
                        if (empty($cred->{$field})) {
                            continue;
                        }

                        $decrypted = $this->decryptWith($cred->{$field}, $oldDerivedKey);
                        $updates[$field] = $this->encryptWith($decrypted, $newDerivedKey);
                    }

                    if ($updates) {
                        $cred->update($updates);
                    }
                } catch (\Throwable $e) {
                    Log::error('Credential rotation failed for tenant', [
                        'tenant_id' => $cred->tenant_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}
