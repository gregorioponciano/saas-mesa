<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Services\EncryptedCredentialService;
use Illuminate\Database\Eloquent\Model;

class TenantEfiCredentials extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id_encrypted',
        'client_secret_encrypted',
        'pix_key_encrypted',
        'account_type',
        'certificate_path_encrypted',
        'certificate_content_encrypted',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function decryptClientId(): string
    {
        return app(EncryptedCredentialService::class)->decrypt($this->client_id_encrypted);
    }

    public function decryptClientSecret(): string
    {
        return app(EncryptedCredentialService::class)->decrypt($this->client_secret_encrypted);
    }

    public function decryptPixKey(): ?string
    {
        if (!$this->pix_key_encrypted) {
            return null;
        }
        return app(EncryptedCredentialService::class)->decrypt($this->pix_key_encrypted);
    }

    public function decryptCertificatePath(): ?string
    {
        if (!$this->certificate_path_encrypted) {
            return null;
        }
        return app(EncryptedCredentialService::class)->decrypt($this->certificate_path_encrypted);
    }

    public function decryptCertificateContent(): ?string
    {
        if (!$this->certificate_content_encrypted) {
            return null;
        }
        return app(EncryptedCredentialService::class)->decrypt($this->certificate_content_encrypted);
    }

    public function toDecryptedArray(): array
    {
        return [
            'client_id' => $this->decryptClientId(),
            'client_secret' => $this->decryptClientSecret(),
            'pix_key' => $this->decryptPixKey(),
            'account_type' => $this->account_type,
            'certificate_path' => $this->decryptCertificatePath(),
            'certificate_content' => $this->decryptCertificateContent(),
        ];
    }
}
