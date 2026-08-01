<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\SaasPlan;
use App\Models\TenantInvoice;
use App\Models\WebhookLog;

it('lista logs de webhooks com stats e filtros', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();
    WebhookLog::create([
        'source' => 'tenant',
        'tenant_id' => $tenant->id,
        'payload_json' => '{"event":"charge_paid"}',
        'is_valid' => true,
        'processed' => true,
    ]);
    WebhookLog::create([
        'source' => 'saas',
        'payload_json' => '{"event":"charge_failed"}',
        'is_valid' => false,
        'processed' => false,
        'error_message' => 'Assinatura inválida',
    ]);

    $this->getJson('/api/superadmin/webhook-logs')
        ->assertOk()
        ->assertJsonCount(2, 'logs.data')
        ->assertJsonPath('stats.total', 2)
        ->assertJsonPath('stats.invalid', 1)
        ->assertJsonPath('stats.errors', 1);

    $this->getJson('/api/superadmin/webhook-logs?has_error=1')
        ->assertOk()
        ->assertJsonCount(1, 'logs.data')
        ->assertJsonPath('logs.data.0.error_message', 'Assinatura inválida');

    $this->getJson('/api/superadmin/webhook-logs?valid=1')
        ->assertOk()
        ->assertJsonCount(1, 'logs.data');
});

it('renderiza a página de webhooks do painel', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.webhooks'))
        ->assertOk()
        ->assertSee('Webhooks EFI');
});

it('lista registros de auditoria com filtros', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant(['name' => 'Empresa Auditada']);
    $this->postJson('/api/superadmin/tenants/'.$tenant->id.'/suspend')->assertOk();

    $this->getJson('/api/superadmin/audit-logs')
        ->assertOk()
        ->assertJsonCount(1, 'logs.data')
        ->assertJsonPath('logs.data.0.action', 'tenant.suspend')
        ->assertJsonPath('logs.data.0.admin_email', $superadmin->email)
        ->assertJsonCount(1, 'actions');

    $this->getJson('/api/superadmin/audit-logs?action=tenant')
        ->assertOk()
        ->assertJsonCount(1, 'logs.data');

    $this->getJson('/api/superadmin/audit-logs?action=plan')
        ->assertOk()
        ->assertJsonCount(0, 'logs.data');
});

it('registra auditoria nas ações de planos, backups, loyalty e settings', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->firstOrFail();
    $tenant = createTenant();

    $this->putJson('/api/superadmin/tenants/'.$tenant->id.'/plan', ['plan_id' => $plan->id])->assertOk();
    $this->postJson('/api/superadmin/loyalty/'.$tenant->id.'/toggle')->assertOk();
    $this->postJson('/api/superadmin/backups', ['tenant_id' => $tenant->id])->assertCreated();
    $this->putJson('/api/superadmin/tenants/'.$tenant->id.'/settings', [
        'name' => 'Nome Atualizado',
        'email' => $tenant->email,
    ])->assertOk();

    $actions = AuditLog::pluck('action')->unique()->sort()->values();

    expect($actions)->toContain('tenant.change_plan')
        ->toContain('loyalty.toggle')
        ->toContain('backup.create')
        ->toContain('tenant.update_settings');
});

it('renderiza a página de auditoria do painel', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.audit'))
        ->assertOk()
        ->assertSee('Auditoria');
});

it('lista assinaturas e faturas no financeiro', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->firstOrFail();
    $tenant = createTenant();

    TenantInvoice::create([
        'tenant_id' => $tenant->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'amount_cents' => 9790,
        'status' => 'paid',
        'paid_at' => now(),
        'items_json' => ['plano' => 'Premium'],
    ]);

    $this->getJson('/api/superadmin/financial/subscriptions')
        ->assertOk()
        ->assertJsonPath('stats.active', 0);

    $this->getJson('/api/superadmin/financial/invoices')
        ->assertOk()
        ->assertJsonPath('stats.total', 1)
        ->assertJsonPath('stats.collected_cents', 9790)
        ->assertJsonCount(1, 'invoices.data')
        ->assertJsonPath('invoices.data.0.tenant_name', $tenant->name);
});

it('renderiza a página de privacidade LGPD do painel', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.privacy'))
        ->assertOk()
        ->assertSee('Privacidade e LGPD')
        ->assertSee('Retenção de backups');
});
