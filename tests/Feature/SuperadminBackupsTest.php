<?php

declare(strict_types=1);

use App\Models\TenantBackup;
use App\Services\TenantBackupService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(TenantBackupService::DISK);
});

it('lista backups de todas as empresas com stats', function () {
    seedPlans();
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenantA = createTenant();
    $tenantB = createTenant();

    $service = app(TenantBackupService::class);
    $service->createBackup($tenantA);
    $service->createBackup($tenantB);
    $service->createBackup($tenantB);

    $this->getJson('/api/superadmin/backups')
        ->assertOk()
        ->assertJsonPath('stats.total_backups', 3)
        ->assertJsonCount(3, 'backups.data');
});

it('cria backup de uma empresa pelo painel superadmin', function () {
    seedPlans();
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();

    $this->postJson('/api/superadmin/backups', ['tenant_id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('backup.tenant_id', $tenant->id);

    expect(TenantBackup::where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('rejeita criação de backup sem tenant válido', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/backups', [])->assertStatus(422);
    $this->postJson('/api/superadmin/backups', ['tenant_id' => 999999])->assertStatus(422);
});

it('exclui backup pelo painel superadmin removendo o arquivo', function () {
    seedPlans();
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();
    $service = app(TenantBackupService::class);
    $backup = $service->createBackup($tenant);

    $this->deleteJson('/api/superadmin/backups/'.$backup->id)
        ->assertOk()
        ->assertJsonPath('message', 'Backup excluído.');

    expect(TenantBackup::find($backup->id))->toBeNull();
});

it('renderiza a página de backups do painel superadmin', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.backups'))
        ->assertOk()
        ->assertSee('Backups');
});
