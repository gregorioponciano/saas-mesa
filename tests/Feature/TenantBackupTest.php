<?php

declare(strict_types=1);

use App\Livewire\Admin\BackupManager;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Services\TenantBackupService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(TenantBackupService::DISK);
});

it('cria um backup com registro, arquivo e metadados corretos', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE]);
    $admin = createTenantAdmin($tenant);
    $this->actingAs($admin);

    Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pizza',
        'price' => 10.0,
        'status' => 'active',
    ]);
    $category = Category::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Massas']);
    Order::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_name' => 'Cliente',
        'status' => 'novo',
        'total' => 10.0,
    ]);

    $this->get(route('dashboard.backup'))->assertOk();

    Livewire::test(BackupManager::class)
        ->call('createBackup')
        ->assertHasNoErrors();

    $backup = TenantBackup::first();
    expect($backup)->not->toBeNull()
        ->and($backup->tenant_id)->toBe($tenant->id)
        ->and($backup->status)->toBe('ready')
        ->and($backup->type)->toBe('manual')
        ->and($backup->expires_at)->not->toBeNull();

    Storage::disk(TenantBackupService::DISK)->assertExists(
        app(TenantBackupService::class)->pathFor($tenant, $backup->filename)
    );

    $content = json_decode(Storage::disk(TenantBackupService::DISK)->get(
        app(TenantBackupService::class)->pathFor($tenant, $backup->filename)
    ), true);

    expect($content)->toHaveKey('tenant')
        ->and($content['tenant']['id'])->toBe($tenant->id)
        ->and($content['data'])->toHaveKey('products')
        ->and($content['data'])->toHaveKey('orders');
});

it('lista os backups do próprio tenant e baixa via rota autenticada', function () {
    seedPlans();
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);
    $this->actingAs($admin);

    $service = app(TenantBackupService::class);
    $service->createBackup($tenant);

    $backup = TenantBackup::first();
    expect($backup)->not->toBeNull();

    $response = $this->get(route('dashboard.backup.download', $backup));
    $response->assertOk();

    $this->assertEquals(
        Storage::disk(TenantBackupService::DISK)->get(
            $service->pathFor($tenant, $backup->filename)
        ),
        $response->streamedContent()
    );
});

it('nega download de backup de outra empresa', function () {
    seedPlans();
    $tenantA = createTenant();
    $tenantB = createTenant();
    $adminA = createTenantAdmin($tenantA);
    $this->actingAs($adminA);

    app(TenantBackupService::class)->createBackup($tenantB);

    $backup = TenantBackup::first();
    expect($backup->tenant_id)->toBe($tenantB->id);

    $this->get(route('dashboard.backup.download', $backup))->assertForbidden();
});

it('purga backups expirados e mantém os não expirados', function () {
    seedPlans();
    $tenant = createTenant();

    $service = app(TenantBackupService::class);

    $expired = $service->createBackup($tenant);
    $expired->update(['expires_at' => now()->subDay()]);

    $kept = $service->createBackup($tenant);

    $deleted = $service->deleteExpired();

    expect($deleted)->toBe(1)
        ->and(TenantBackup::find($expired->id))->toBeNull()
        ->and(TenantBackup::find($kept->id))->not->toBeNull();
});

it('aplica retenção ilimitada para plano pago e limite de quantidade para o gratuito', function () {
    seedPlans();
    $tenant = createTenant();

    $service = app(TenantBackupService::class);

    $free = createTenant(['plan' => Tenant::PLAN_FREE]);
    expect($service->retentionDaysForTenant($free))->toBe(7);

    $paid = createTenant(['plan' => Tenant::PLAN_PAID]);
    expect($service->retentionDaysForTenant($paid))->toBeNull();

    foreach (range(1, 5) as $i) {
        $service->createBackup($free);
    }

    expect($free->backups()->count())->toBe(3);

    $tenantBackups = TenantBackup::where('tenant_id', $tenant->id)->count();
    expect($tenantBackups)->toBe(0);
});
