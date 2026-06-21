<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SmtpSettings extends Component
{
    public string $mailHost = '';
    public string $mailPort = '';
    public string $mailUsername = '';
    public string $mailPassword = '';
    public string $mailEncryption = '';
    public string $mailFromAddress = '';
    public string $mailFromName = '';

    protected function rules(): array
    {
        return [
            'mailHost' => 'nullable|string|max:255',
            'mailPort' => 'nullable|string|max:10',
            'mailUsername' => 'nullable|string|max:255',
            'mailPassword' => 'nullable|string|max:255',
            'mailEncryption' => 'nullable|string|max:10',
            'mailFromAddress' => 'nullable|email|max:255',
            'mailFromName' => 'nullable|string|max:255',
        ];
    }

    public function mount(): void
    {
        $tenant = Auth::user()->tenant;
        $this->mailHost = $tenant->mail_host ?? '';
        $this->mailPort = $tenant->mail_port ?? '';
        $this->mailUsername = $tenant->mail_username ?? '';
        $this->mailPassword = $tenant->mail_password ?? '';
        $this->mailEncryption = $tenant->mail_encryption ?? '';
        $this->mailFromAddress = $tenant->mail_from_address ?? '';
        $this->mailFromName = $tenant->mail_from_name ?? '';
    }

    public function save(): void
    {
        $this->validate();

        $tenant = Auth::user()->tenant;
        $tenant->update([
            'mail_host' => $this->mailHost ?: null,
            'mail_port' => $this->mailPort ?: null,
            'mail_username' => $this->mailUsername ?: null,
            'mail_password' => $this->mailPassword ?: null,
            'mail_encryption' => $this->mailEncryption ?: null,
            'mail_from_address' => $this->mailFromAddress ?: null,
            'mail_from_name' => $this->mailFromName ?: null,
        ]);

        $this->dispatch('notify', message: 'Configuracoes de email salvas!');
    }

    public function render()
    {
        return view('livewire.admin.smtp-settings', [
            'tenant' => Auth::user()->tenant,
        ])->extends('layouts.admin');
    }
}
