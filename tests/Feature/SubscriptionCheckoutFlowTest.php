<?php

declare(strict_types=1);

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Services\EfiBank\SaasEfiBankService;

test('checkout premium cria nova assinatura pendente com cobranca PIX', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['status' => 'active']);
    $user = createTenantAdmin($tenant);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('createSubscription')->once()->andReturnUsing(function (Tenant $t, SaasPlan $p) {
            return SaasSubscription::factory()->create([
                'tenant_id' => $t->id,
                'plan_id' => $p->id,
                'status' => 'pending',
            ]);
        });
    });

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'premium'])
        ->assertRedirect(route('subscription.checkout'));

    $created = SaasSubscription::where('tenant_id', $tenant->id)->first();
    expect($created)->not->toBeNull();
    expect($created->status)->toBe('pending');
    expect($created->plan_id)->toBe($premium->id);
    expect(session('payment_pending'))->toBe($created->id);
});

test('checkout premium renova o mesmo plano reutilizando a assinatura ativa', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $user = createTenantAdmin($tenant);

    $subscription = SaasSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $premium->id,
        'status' => 'active',
        'current_period_start' => now()->subMonth(),
        'current_period_end' => now()->addDays(10),
    ]);
    $tenant->update(['subscription_id' => $subscription->id]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('chargeSubscription')->once();
    });

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'premium'])
        ->assertRedirect(route('subscription.checkout'))
        ->assertSessionHas('payment_pending', (string) $subscription->id);

    expect($subscription->fresh()->status)->toBe('pending');
    expect($subscription->fresh()->plan_id)->toBe($premium->id);
});

test('checkout reutiliza PIX pendente nao expirado do mesmo plano', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $user = createTenantAdmin($tenant);

    $pending = SaasSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $premium->id,
        'status' => 'pending',
        'metadata' => ['months' => 1, 'expires_at' => now()->addHour()->toIso8601String()],
    ]);
    $tenant->update(['subscription_id' => $pending->id]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('pixDetails')->once()->andReturn(['expired' => false]);
        $mock->shouldNotReceive('createSubscription');
        $mock->shouldNotReceive('chargeSubscription');
    });

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'premium'])
        ->assertRedirect(route('subscription.checkout'))
        ->assertSessionHas('payment_pending', (string) $pending->id);

    expect($pending->fresh()->status)->toBe('pending');
});

test('checkout gera PIX novo quando o pendente expirou', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $user = createTenantAdmin($tenant);

    $expired = SaasSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $premium->id,
        'status' => 'pending',
        'metadata' => ['months' => 1, 'expires_at' => now()->subMinute()->toIso8601String()],
    ]);
    $tenant->update(['subscription_id' => $expired->id]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('pixDetails')->once()->andReturn(['expired' => true]);
        $mock->shouldReceive('chargeSubscription')->once();
    });

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'premium'])
        ->assertRedirect(route('subscription.checkout'))
        ->assertSessionHas('payment_pending', (string) $expired->id);

    expect($expired->fresh()->status)->toBe('pending');
    expect($expired->fresh()->metadata)->toBeNull();
});

test('checkout upgrade de plano gratis para premium cria nova assinatura', function () {
    seedPlans();
    $free = SaasPlan::where('slug', 'free')->first();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['status' => 'active']);
    $user = createTenantAdmin($tenant);

    $old = SaasSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $free->id,
        'status' => 'active',
    ]);
    $tenant->update(['subscription_id' => $old->id]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('createSubscription')->once()->andReturnUsing(function (Tenant $t, SaasPlan $p) {
            return SaasSubscription::factory()->create([
                'tenant_id' => $t->id,
                'plan_id' => $p->id,
                'status' => 'pending',
            ]);
        });
    });

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'premium'])
        ->assertRedirect(route('subscription.checkout'));

    $new = SaasSubscription::where('tenant_id', $tenant->id)
        ->where('id', '!=', $old->id)
        ->first();
    expect($new)->not->toBeNull();
    expect($new->status)->toBe('pending');
    expect(session('payment_pending'))->toBe($new->id);

    expect($old->fresh()->status)->toBe('active');
});

test('checkout bloqueia downgrade de plano pago para gratuito', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $user = createTenantAdmin($tenant);

    SaasSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $premium->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'free'])
        ->assertRedirect(route('subscription.checkout'))
        ->assertSessionHas('error', 'Você está em um plano pago. Não é possível voltar ao plano Gratuito — faça upgrade ou renove seu plano.');

    expect($tenant->fresh()->plan)->toBe(Tenant::PLAN_PAID);
});

test('checkout gratuito ativa o plano gratis e zera o override', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'max_tables' => 80, 'status' => 'active']);
    $user = createTenantAdmin($tenant);
    $tenant->update(['plan' => Tenant::PLAN_FREE]);

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'free'])
        ->assertRedirect('/dashboard');

    $tenant->refresh();
    expect($tenant->plan)->toBe(Tenant::PLAN_FREE);
    expect($tenant->max_tables)->toBeNull();
    expect($tenant->maxTablesAllowed())->toBe(2);

    $subscription = SaasSubscription::where('tenant_id', $tenant->id)->latest()->first();
    expect($subscription->status)->toBe('active');
    expect($subscription->plan?->slug)->toBe('free');
});

test('checkout premium renova mesmo plano paga mais caro nao faz downgrade', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $user = createTenantAdmin($tenant);

    SaasSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $premium->id,
        'status' => 'active',
    ]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('chargeSubscription')->once();
    });

    $this->actingAs($user)
        ->post('/subscription', ['plan' => 'premium'])
        ->assertRedirect(route('subscription.checkout'));
});
