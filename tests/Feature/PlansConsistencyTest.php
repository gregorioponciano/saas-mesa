<?php

declare(strict_types=1);

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Services\EfiBank\SaasEfiBankService;

test('criar plano aceita features_json como objeto (envio do front)', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $response = $this->postJson('/api/superadmin/plans', [
        'name' => 'Teste Objeto',
        'price_cents' => 9900,
        'interval' => 'month',
        'features_json' => [
            'max_tables' => 10,
            'max_users' => 5,
            'reports' => true,
        ],
        'is_active' => true,
    ]);

    $response->assertCreated();

    $plan = SaasPlan::where('slug', 'teste-objeto')->first();
    expect($plan)->not->toBeNull();
    expect($plan->features_json)->toBe([
        'max_tables' => 10,
        'max_users' => 5,
        'reports' => true,
    ]);
});

test('criar plano aceita features_json como string JSON (compatibilidade)', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $response = $this->postJson('/api/superadmin/plans', [
        'name' => 'Teste String',
        'price_cents' => 4900,
        'interval' => 'month',
        'features_json' => json_encode(['max_tables' => 7]),
        'is_active' => true,
    ]);

    $response->assertCreated();

    $plan = SaasPlan::where('slug', 'teste-string')->first();
    expect($plan->features_json)->toBe(['max_tables' => 7]);
});

test('visibleFeatures inclui descrições booleanas com V/X', function () {
    $plan = SaasPlan::create([
        'name' => 'Completo',
        'slug' => 'completo',
        'price_cents' => 19900,
        'interval' => 'month',
        'features_json' => [
            'max_tables' => 50,
            'max_users' => 20,
            'cardapio_ilimitado' => true,
            'programa_fidelidade' => false,
        ],
        'is_active' => true,
    ]);

    $items = $plan->visibleFeatures();

    expect($items)->toHaveCount(4);
    expect($items[0])->toBe(['key' => 'max_tables', 'label' => 'Mesas máximas', 'value' => 50]);
    expect($items[1])->toBe(['key' => 'max_users', 'label' => 'Usuários máximos', 'value' => 20]);
    expect($items[2])->toBe(['key' => 'cardapio_ilimitado', 'label' => 'Cardápio digital ilimitado', 'value' => true]);
    expect($items[3])->toBe(['key' => 'programa_fidelidade', 'label' => 'Programa de fidelidade (pontos)', 'value' => false]);
});

test('atualizar plano aceita features_json como objeto (erro "must be a valid JSON string")', function () {
    $superadmin = createSuperAdmin();
    $plan = SaasPlan::create([
        'name' => 'Antigo',
        'slug' => 'antigo',
        'price_cents' => 4900,
        'interval' => 'month',
        'features_json' => ['max_tables' => 5],
        'is_active' => true,
    ]);

    $this->actingAs($superadmin)
        ->putJson('/api/superadmin/plans/'.$plan->id, [
            'name' => 'Antigo',
            'price_cents' => 5900,
            'interval' => 'month',
            'features_json' => [
                'max_tables' => 10,
                'max_products' => 50,
                'max_users' => 5,
                'cardapio_ilimitado' => true,
                'multi_usuarios' => false,
            ],
            'is_active' => true,
        ])
        ->assertOk();

    $plan->refresh();
    expect($plan->price_cents)->toBe(5900);
    expect($plan->features_json['max_tables'])->toBe(10);
    expect($plan->features_json['cardapio_ilimitado'])->toBeTrue();
    expect($plan->features_json['multi_usuarios'])->toBeFalse();
});

test('criar plano aceita cobrança trimestral, semestral e rejeita inválida', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    foreach (['quarter', 'semiannual'] as $interval) {
        $this->postJson('/api/superadmin/plans', [
            'name' => 'Plano '.$interval,
            'price_cents' => 9900,
            'interval' => $interval,
            'features_json' => ['max_tables' => 10],
            'is_active' => true,
        ])->assertCreated();

        expect(SaasPlan::where('slug', 'plano-'.$interval)->exists())->toBeTrue();
    }

    $this->postJson('/api/superadmin/plans', [
        'name' => 'Plano Invalido',
        'price_cents' => 9900,
        'interval' => 'bimonthly',
        'features_json' => ['max_tables' => 10],
        'is_active' => true,
    ])->assertUnprocessable();
});

test('criar e atualizar plano aceita cores de borda e fundo', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $response = $this->postJson('/api/superadmin/plans', [
        'name' => 'Colorido',
        'price_cents' => 12900,
        'interval' => 'month',
        'features_json' => ['max_tables' => 30],
        'border_color' => '#7c3aed',
        'background_color' => '#1e1b4b',
        'is_active' => true,
    ]);

    $response->assertCreated();

    $plan = SaasPlan::where('slug', 'colorido')->first();
    expect($plan->border_color)->toBe('#7c3aed');
    expect($plan->background_color)->toBe('#1e1b4b');

    $this->putJson('/api/superadmin/plans/'.$plan->id, [
        'border_color' => null,
        'background_color' => '#0f172a',
    ])->assertOk();

    $plan->refresh();
    expect($plan->border_color)->toBeNull();
    expect($plan->background_color)->toBe('#0f172a');
});

test('criar plano aceita descrições booleanas (V/X) enviadas pelo formulário', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $response = $this->postJson('/api/superadmin/plans', [
        'name' => 'Teste Descricoes',
        'price_cents' => 19900,
        'interval' => 'month',
        'features_json' => [
            'max_tables' => 100,
            'max_products' => 200,
            'max_users' => 2000,
            'cardapio_ilimitado' => true,
            'pedidos_ilimitados' => true,
            'cupons_desconto' => true,
            'delivery_entregadores' => true,
            'programa_fidelidade' => false,
            'relatorios_avancados' => false,
            'suporte_prioritario' => false,
            'multi_usuarios' => false,
        ],
        'is_active' => true,
    ]);

    $response->assertCreated();

    $plan = SaasPlan::where('slug', 'teste-descricoes')->first();
    expect($plan)->not->toBeNull();
    expect($plan->features_json['delivery_entregadores'])->toBeTrue();
    expect($plan->features_json['multi_usuarios'])->toBeFalse();
});

test('checkout do tenant exibe as mesmas features dos planos', function () {
    seedPlans();
    $tenant = createTenant(['status' => 'active', 'plan' => 'paid']);
    $user = createTenantAdmin($tenant);

    $this->actingAs($user)
        ->get('/subscription')
        ->assertOk()
        ->assertSee('Até 2 mesas')
        ->assertSee('Cardápio digital ilimitado')
        ->assertSee('Pedidos ilimitados')
        ->assertSee('Mesas ilimitadas')
        ->assertSee('Múltiplos usuários')
        ->assertSee('Suporte prioritário');
});

test('checkout do tenant lista todos os planos ativos', function () {
    seedPlans();
    SaasPlan::create([
        'name' => 'Teste',
        'slug' => 'teste',
        'price_cents' => 15000,
        'interval' => 'month',
        'features_json' => ['max_tables' => 100, 'max_users' => 2000, 'max_products' => 200],
        'is_active' => true,
    ]);

    $tenant = createTenant(['status' => 'active', 'plan' => 'free']);
    $user = createTenantAdmin($tenant);

    $this->actingAs($user)
        ->get('/subscription')
        ->assertOk()
        ->assertSee('Teste')
        ->assertSee('R$ 150,00')
        ->assertSee('Assinar Teste');
});

test('assinatura aceita plano customizado ativo', function () {
    seedPlans();
    $plan = SaasPlan::create([
        'name' => 'Custom',
        'slug' => 'custom',
        'price_cents' => 7500,
        'interval' => 'month',
        'features_json' => ['max_tables' => 30],
        'is_active' => true,
    ]);

    $tenant = createTenant(['status' => 'trial', 'plan' => 'free']);
    $user = createTenantAdmin($tenant);

    $this->mock(SaasEfiBankService::class, function ($mock) use ($plan, $tenant) {
        $mock->shouldReceive('createSubscription')
            ->once()
            ->andReturnUsing(function () use ($plan, $tenant) {
                return SaasSubscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'pending',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                ]);
            });
    });

    $this->actingAs($user)
        ->post(route('subscription.checkout.store'), [
            'plan' => $plan->slug,
            'months' => 1,
        ])
        ->assertRedirect(route('subscription.checkout'));

    $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();
    expect($subscription)->not->toBeNull();
    expect($subscription->plan_id)->toBe($plan->id);
    expect($subscription->status)->toBe('pending');
});
