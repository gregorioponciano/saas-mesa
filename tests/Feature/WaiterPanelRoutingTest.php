<?php

declare(strict_types=1);

use App\Models\Tenant;

it('permite atendente no painel do próprio tenant pago', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $staff = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);

    $this->actingAs($staff)
        ->get('/painel/'.$tenant->slug)
        ->assertOk();
});

it('bloqueia acesso ao painel de outro tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();
    $staff = createTenantAdmin($tenantA, ['role' => 'atendente', 'is_staff' => false]);

    $this->actingAs($staff)
        ->get('/painel/'.$tenantB->slug)
        ->assertForbidden();
});

it('bloqueia acesso ao painel para usuário de tenant free', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE]);
    $staff = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);

    $this->actingAs($staff)
        ->get('/painel/'.$tenant->slug)
        ->assertForbidden();
});

it('bloqueia suporte do painel para tenant free', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE]);
    $staff = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);

    $this->actingAs($staff)
        ->get('/painel/'.$tenant->slug.'/suporte')
        ->assertForbidden();
});

it('gera 404 para configuracoes do painel (rota removida)', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $staff = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);

    $this->actingAs($staff)
        ->get('/painel/'.$tenant->slug.'/configuracoes')
        ->assertNotFound();
});

it('gera 404 para tenant inexistente no painel', function () {
    $tenant = createTenant();
    $staff = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);

    $this->actingAs($staff)
        ->get('/painel/slug-inexistente')
        ->assertNotFound();
});

it('redireciona usuário cliente sem tenant ao login em vez de dar 500', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'is_staff' => false]);
    $user->update(['tenant_id' => null]);

    $this->actingAs($user)
        ->get('/painel/'.$tenant->slug)
        ->assertRedirect(route('login'));
});

it('bloqueia visitante sem autenticação ao acessar painel restaurante', function () {
    $this->get('/painel/algum-slug')->assertRedirect(route('login'));
});
