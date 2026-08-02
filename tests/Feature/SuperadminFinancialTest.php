<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\SaasPaymentHistory;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\TenantInvoice;

it('lista pagamentos com filtros', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();
    $plan = SaasPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_cents' => 9900,
        'interval' => 'month',
        'is_active' => true,
    ]);
    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'payment_method' => 'pix',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
        'next_billing_date' => now()->addMonth(),
    ]);

    SaasPaymentHistory::create([
        'subscription_id' => $subscription->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 4990,
        'status' => 'paid',
        'method' => 'pix',
        'paid_at' => now(),
    ]);
    SaasPaymentHistory::create([
        'subscription_id' => $subscription->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 1990,
        'status' => 'refunded',
        'method' => 'pix',
        'paid_at' => now(),
    ]);

    $this->getJson('/api/superadmin/financial/payments?status=paid')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount_cents', 4990);
});

it('lista faturas com stats agregados', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();
    TenantInvoice::create([
        'tenant_id' => $tenant->id,
        'amount_cents' => 4990,
        'status' => 'paid',
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'items_json' => '[]',
    ]);
    TenantInvoice::create([
        'tenant_id' => $tenant->id,
        'amount_cents' => 3990,
        'status' => 'open',
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'items_json' => '[]',
    ]);

    $this->getJson('/api/superadmin/financial/invoices')
        ->assertOk()
        ->assertJsonCount(2, 'invoices.data')
        ->assertJsonPath('stats.total', 2)
        ->assertJsonPath('stats.open_cents', 3990)
        ->assertJsonPath('stats.collected_cents', 4990);
});

it('retorna o financeiro de um tenant específico', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();
    $plan = SaasPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_cents' => 9900,
        'interval' => 'month',
        'is_active' => true,
    ]);
    SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'payment_method' => 'pix',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
        'next_billing_date' => now()->addMonth(),
    ]);

    $this->getJson("/api/superadmin/financial/tenant/{$tenant->id}")
        ->assertOk()
        ->assertJsonPath('tenant.name', $tenant->name)
        ->assertJsonPath('subscription.status', 'active');
});

it('cria, atualiza e exclui planos registrando auditoria', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/plans', [
        'name' => 'Essencial',
        'price_cents' => 2900,
        'interval' => 'month',
        'is_active' => true,
    ])->assertCreated();

    $plan = SaasPlan::where('name', 'Essencial')->first();
    expect($plan)->not->toBeNull();

    $this->putJson("/api/superadmin/plans/{$plan->id}", [
        'name' => 'Essencial Plus',
        'price_cents' => 3900,
        'interval' => 'month',
        'is_active' => true,
    ])->assertOk();

    expect(SaasPlan::find($plan->id)->price_cents)->toBe(3900);

    $this->deleteJson("/api/superadmin/plans/{$plan->id}")->assertStatus(204);
    expect(SaasPlan::find($plan->id))->toBeNull();

    expect(AuditLog::where('action', 'plan.create')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'plan.update')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'plan.delete')->exists())->toBeTrue();
});

it('valida dados obrigatórios ao criar plano', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/plans', [
        'name' => '',
        'price_cents' => 'nao-numerico',
    ])->assertStatus(422);

    expect(SaasPlan::count())->toBe(0);
});

it('bloqueia criação de plano duplicado', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    SaasPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_cents' => 9900,
        'interval' => 'month',
        'is_active' => true,
    ]);

    $this->postJson('/api/superadmin/plans', [
        'name' => 'Pro',
        'price_cents' => 7900,
        'interval' => 'month',
        'is_active' => true,
    ])->assertStatus(422);
});

it('renderiza a página de planos com preço e features em português', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.plans'))
        ->assertOk()
        ->assertSee('Planos')
        ->assertSee('Novo Plano')
        ->assertSee('Mesas máximas')
        ->assertSee('por mês');
});
