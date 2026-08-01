<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Services\TenantBackupService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class BackupManager extends Component
{
    use WithPagination;

    public bool $creating = false;

    public bool $creatingDone = false;

    public ?string $error = null;

    public function boot(TenantBackupService $backupService): void
    {
        $this->backupService = $backupService;
    }

    private TenantBackupService $backupService;

    public bool $hasTenant = true;

    public function mount(): void
    {
        $this->hasTenant = Auth::user()?->tenant !== null;
    }

    public function createBackup(): void
    {
        $tenant = $this->tenant();

        if ($this->creating) {
            return;
        }

        $this->creating = true;
        $this->creatingDone = false;
        $this->error = null;

        try {
            $this->backupService->createBackup($tenant);
            $this->creatingDone = true;
            $this->dispatch('notify', message: 'Backup criado com sucesso!');
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'Não foi possível criar o backup agora. Tente novamente em instantes.';
        } finally {
            $this->creating = false;
        }
    }

    public function deleteBackup(int $backupId): void
    {
        $backup = TenantBackup::where('tenant_id', $this->tenant()->id)->findOrFail($backupId);

        try {
            $this->backupService->deleteBackup($backup);
            $this->dispatch('notify', message: 'Backup excluído.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', message: 'Falha ao excluir o backup.');
        }
    }

    public function tenant(): Tenant
    {
        $tenant = Auth::user()->tenant;

        abort_if(! $tenant, 404, 'Nenhuma empresa vinculada a este usuário.');

        return $tenant;
    }

    #[Computed]
    public function retentionLabel(): string
    {
        return $this->backupService->retentionLabel($this->tenant());
    }

    #[Computed]
    public function totalSize(): string
    {
        $bytes = $this->backupService->totalSizeForTenant($this->tenant());

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.').' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.').' KB';
        }

        return $bytes.' B';
    }

    public function render()
    {
        if (! $this->hasTenant) {
            return view('livewire.admin.backup-manager', ['backups' => collect(), 'noTenant' => true])
                ->extends('layouts.admin');
        }

        $backups = TenantBackup::where('tenant_id', $this->tenant()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.admin.backup-manager', ['backups' => $backups, 'noTenant' => false])
            ->extends('layouts.admin');
    }
}
