<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\TenantEfiCredentials;
use App\Services\EfiBank\EfiBankClient;
use App\Services\EncryptedCredentialService;
use Livewire\Component;
use Livewire\WithFileUploads;

class EfiCredentialsManager extends Component
{
    use WithFileUploads;

    public ?string $client_id = null;
    public ?string $client_secret = null;
    public ?string $pix_key = null;
    public ?string $cert_password = null;
    public string $account_type = 'production';
    public $certificate_file = null;
    public bool $has_credentials = false;
    public ?string $masked_client_id = null;
    public ?string $masked_pix_key = null;
    public ?string $account_type_display = null;
    public bool $test_result = false;
    public ?string $test_message = null;
    public bool $testing = false;
    public bool $saving = false;
    public bool $saved = false;
    public ?string $error = null;

    private EncryptedCredentialService $encryptionService;

    public function boot(EncryptedCredentialService $encryptionService): void
    {
        $this->encryptionService = $encryptionService;
    }

    public function mount(): void
    {
        $this->loadCredentials();
    }

    public function loadCredentials(): void
    {
        $tenant = auth()->user()->tenant;
        $credentials = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();

        if ($credentials) {
            $this->has_credentials = true;
            $this->account_type = $credentials->account_type;
            $this->account_type_display = $credentials->account_type === 'production' ? 'Produção' : 'Sandbox';

            try {
                $this->masked_client_id = $this->mask($credentials->decryptClientId());
                if ($credentials->pix_key_encrypted) {
                    $this->masked_pix_key = $this->mask($credentials->decryptPixKey() ?? '');
                }
            } catch (\Throwable) {
                $this->masked_client_id = '*** erro ao descriptografar ***';
            }
        }
    }

    public function save(): void
    {
        $this->saving = true;
        $this->saved = false;
        $this->error = null;

        $this->validate([
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:255'],
            'pix_key' => ['required', 'string', 'max:255'],
            'cert_password' => ['nullable', 'string', 'max:255'],
            'account_type' => ['required', 'in:sandbox,production'],
            'certificate_file' => ['nullable', 'file', 'mimes:p12', 'max:2048'],
        ]);

        try {
            $tenant = auth()->user()->tenant;

            $certificateContent = null;
            if ($this->certificate_file) {
                $certificateContent = $this->certificate_file->get();
            } elseif ($this->has_credentials) {
                $existing = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();
                if ($existing && $existing->certificate_content_encrypted) {
                    $certificateContent = $this->encryptionService->decrypt($existing->certificate_content_encrypted);
                }
            }

            $encrypted = $this->encryptionService->encryptTenantCredentials([
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'pix_key' => $this->pix_key,
                'certificate_content' => $certificateContent ?? '',
                'cert_password' => $this->cert_password,
            ]);

            TenantEfiCredentials::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'client_id_encrypted' => $encrypted['client_id_encrypted'],
                    'client_secret_encrypted' => $encrypted['client_secret_encrypted'],
                    'pix_key_encrypted' => $encrypted['pix_key_encrypted'],
                    'certificate_content_encrypted' => $encrypted['certificate_content_encrypted'],
                    'cert_password_encrypted' => $encrypted['cert_password_encrypted'],
                    'account_type' => $this->account_type,
                    'is_active' => true,
                ]
            );

            $this->saved = true;
            $this->has_credentials = true;
            $this->loadCredentials();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Credenciais EfiBank salvas com sucesso!',
            ]);

        } catch (\Throwable $e) {
            $this->error = 'Erro ao salvar: ' . $e->getMessage();
        } finally {
            $this->saving = false;
        }
    }

    public function testConnection(): void
    {
        $this->testing = true;
        $this->test_message = null;
        $this->test_result = false;

        try {
            $tenant = auth()->user()->tenant;

            $client = EfiBankClient::forTenant($tenant);
            $token = $client->getAccessToken();

            $this->test_result = true;
            $this->test_message = 'Conexão com EfiBank estabelecida com sucesso! (' . strtoupper($this->account_type) . ')';

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $this->test_message,
            ]);

        } catch (\Throwable $e) {
            $this->test_result = false;
            $this->test_message = 'Falha na conexão: ' . $e->getMessage();
        } finally {
            $this->testing = false;
        }
    }

    private function mask(?string $value): string
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

    public function clearFields(): void
    {
        $this->client_id = null;
        $this->client_secret = null;
        $this->pix_key = null;
        $this->cert_password = null;
        $this->certificate_file = null;
        $this->error = null;
        $this->saved = false;
    }

    public function render()
    {
        return view('livewire.admin.efi-credentials-manager')
            ->extends('layouts.admin');
    }
}
