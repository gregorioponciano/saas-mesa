<?php

use App\Models\Tenant;
use App\Models\User;

test('pagina de assinatura requer autenticacao', function () {
    $response = $this->get('/subscription');
    $response->assertRedirect('/login');
});

test('usuario pode assinar plano premium', function () {
    $tenant = Tenant::factory()->create([
        'plan' => Tenant::PLAN_FREE,
        'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
        'status' => 'active',
    ]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->post('/subscription', [
        'plan' => 'paid',
    ]);

    $response->assertRedirect('/dashboard');
    $response->assertSessionHas('success');

    $tenant->refresh();
    expect($tenant->plan)->toBe(Tenant::PLAN_PAID);
    expect($tenant->max_tables)->toBe(Tenant::PLAN_MAX_TABLES[Tenant::PLAN_PAID]);
    expect($tenant->status)->toBe('active');
});

test('usuario pode cancelar assinatura premium', function () {
    $tenant = Tenant::factory()->create([
        'plan' => Tenant::PLAN_PAID,
        'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_PAID],
        'status' => 'active',
        'subscription_id' => 'sub_test123',
    ]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->post('/subscription/cancel');

    $response->assertRedirect('/dashboard');

    $tenant->refresh();
    expect($tenant->plan)->toBe(Tenant::PLAN_FREE);
    expect($tenant->max_tables)->toBe(Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE]);
    expect($tenant->subscription_id)->toBeNull();
});
