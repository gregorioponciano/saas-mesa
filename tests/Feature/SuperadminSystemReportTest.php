<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('retorna o relatório completo do sistema autenticado como superadmin', function () {
    seedPlans();
    $superadmin = createSuperAdmin();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    createTenantAdmin($tenant);
    Order::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    TenantBackup::create([
        'tenant_id' => $tenant->id,
        'uuid' => (string) Str::uuid(),
        'filename' => 'b.json',
        'size_bytes' => 512,
    ]);

    $this->actingAs($superadmin)
        ->getJson('/api/superadmin/system/report')
        ->assertOk()
        ->assertJsonStructure([
            'generated_at',
            'system' => ['app_name', 'app_env', 'app_debug', 'laravel_version', 'php_version', 'memory_limit'],
            'connections' => [
                'database' => ['ok', 'driver', 'name'],
                'cache' => ['ok', 'driver'],
                'queue' => ['driver'],
                'storage' => ['writable'],
                'integrations' => ['efi_configured_tenants', 'smtp_configured_tenants'],
            ],
            'errors' => ['failed_jobs', 'failed_webhooks_24h', 'recent_log_errors'],
            'status' => [
                'tenants_by_status',
                'tenants_by_plan',
                'subscriptions_by_status',
                'totals' => ['tenants', 'users', 'orders', 'backups'],
            ],
            'resources' => ['backups_size_bytes', 'disk_free_bytes', 'disk_used_percent'],
            'scheduler',
        ])
        ->assertJsonPath('status.totals.tenants', 1)
        ->assertJsonPath('status.totals.orders', 3)
        ->assertJsonPath('status.totals.users', 2)
        ->assertJsonPath('resources.backups_size_bytes', 512);
});

it('reporta falhas de webhooks e jobs no relatório', function () {
    $superadmin = createSuperAdmin();
    $tenant = createTenant();

    WebhookLog::create([
        'tenant_id' => $tenant->id,
        'source' => 'efi',
        'payload_json' => '{}',
        'is_valid' => false,
        'processed' => false,
        'error_message' => 'assinatura inválida',
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'teste',
        'failed_at' => now(),
    ]);

    $this->actingAs($superadmin)
        ->getJson('/api/superadmin/system/report')
        ->assertOk()
        ->assertJsonPath('errors.failed_webhooks_24h', 1)
        ->assertJsonPath('errors.failed_jobs', 1);
});

it('lista a auditoria recente no relatório', function () {
    $superadmin = createSuperAdmin();
    $tenant = createTenant();

    AuditLog::create([
        'admin_user_id' => $superadmin->id,
        'tenant_id' => $tenant->id,
        'action' => 'tenant.suspended',
        'entity_type' => 'Tenant',
        'entity_id' => (string) $tenant->id,
        'description' => 'Empresa suspensa',
    ]);

    $this->actingAs($superadmin)
        ->getJson('/api/superadmin/system/report')
        ->assertOk()
        ->assertJsonCount(1, 'recent_audit')
        ->assertJsonPath('recent_audit.0.description', 'Empresa suspensa');
});

it('nega o relatório do sistema para usuário sem permissão', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $this->actingAs($admin)
        ->getJson('/api/superadmin/system/report')
        ->assertForbidden();

    $this->getJson('/api/superadmin/system/report')->assertForbidden();
});

it('renderiza o dashboard do superadmin com visão geral', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.dashboard'))
        ->assertOk()
        ->assertSee('Visão Geral')
        ->assertSee('Relatórios')
        ->assertSee('Financeiro')
        ->assertSee('Empresas ativas')
        ->assertSee('Alertas em tempo real')
        ->assertSee('Ao vivo')
        ->assertSee('Empresas recentes')
        ->assertSee('Auditoria recente');
});

it('renderiza a página de relatórios do superadmin', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.reports'))
        ->assertOk()
        ->assertSee('Relatórios')
        ->assertSee('Integridade do sistema')
        ->assertSee('Tarefas agendadas')
        ->assertSee('Alertas e erros')
        ->assertSee('Status da plataforma')
        ->assertSee('Auditoria recente');
});

it('nega acesso ao relatório para usuário comum', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin', 'tenant_id' => null]);

    expect($superadmin->isSuperAdmin())->toBeTrue();
});
