<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\TenantEfiCredentials;
use App\Services\EfiBank\TenantEfiCredentialsService;
use App\Services\EncryptedCredentialService;
use Livewire\Component;
use Livewire\WithFileUploads;

class EfiCredentialsManager extends Component
{
    use WithFileUploads;

    public ?string $client_id = null;
    public ?string $client_secret = null;
    public ?string $pix_key = null;
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

    private TenantEfiCredentialsService $credentialsService;
    private EncryptedCredentialService $encryptionService;

    public function boot(
        TenantEfiCredentialsService $credentialsService,
        EncryptedCredentialService $encryptionService,
    ): void {
        $this->credentialsService = $credentialsService;
        $this->encryptionService = $encryptionService;
    }

    public function mount(): void
    {
        $this->loadCredentials();
    }

    public function loadCredentials(): void
    {
        $tenant = auth()->user()->tenant;
        $data = $this->credentialsService->show($tenant);

        if ($data['configured']) {
            $this->has_credentials = true;
            $this->account_type = $data['account_type'];
            $this->account_type_display = $data['account_type_display'];
            $this->masked_client_id = $data['client_id_masked'];
            $this->masked_pix_key = $data['pix_key_masked'];
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

            $this->credentialsService->save($tenant, [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'pix_key' => $this->pix_key,
                'account_type' => $this->account_type,
            ], $certificateContent);

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
            $result = $this->credentialsService->test($tenant);

            $this->test_result = $result['success'];
            $this->test_message = $result['success']
                ? $result['message'] . ' (' . strtoupper($this->account_type) . ')'
                : $result['message'];

            $this->dispatch('notify', [
                'type' => $result['success'] ? 'success' : 'error',
                'message' => $this->test_message,
            ]);

        } catch (\Throwable $e) {
            $this->test_result = false;
            $this->test_message = 'Não foi possível conectar. Verifique suas credenciais.';
        } finally {
            $this->testing = false;
        }
    }

    public function clearFields(): void
    {
        $this->client_id = null;
        $this->client_secret = null;
        $this->pix_key = null;
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
