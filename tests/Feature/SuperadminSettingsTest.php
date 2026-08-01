<?php

declare(strict_types=1);

it('superadmin visualiza as configurações de uma empresa', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant([
        'name' => 'Minha Lanchonete',
        'whatsapp' => '(11) 99999-9999',
        'delivery_cost_per_order' => 5.5,
    ]);

    $this->getJson('/api/superadmin/tenants/'.$tenant->id.'/settings')
        ->assertOk()
        ->assertJsonPath('tenant.name', 'Minha Lanchonete')
        ->assertJsonPath('tenant.whatsapp', '(11) 99999-9999')
        ->assertJsonPath('tenant.delivery_cost_per_order', 5.5)
        ->assertJsonPath('tenant.plan', $tenant->plan);
});

it('superadmin edita as configurações de uma empresa', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant(['name' => 'Antigo Nome']);

    $this->putJson('/api/superadmin/tenants/'.$tenant->id.'/settings', [
        'name' => 'Novo Nome',
        'email' => $tenant->email,
        'whatsapp' => '(11) 88888-8888',
        'opening_time' => '10:00',
        'closing_time' => '22:30',
        'delivery_cost_enabled' => true,
        'delivery_cost_per_order' => 7.5,
        'delivery_cost_per_km' => 1.5,
        'delivery_radius' => 12,
    ])->assertOk()
        ->assertJsonPath('tenant.name', 'Novo Nome')
        ->assertJsonPath('tenant.whatsapp', '(11) 88888-8888')
        ->assertJsonPath('tenant.opening_time', '10:00')
        ->assertJsonPath('tenant.delivery_cost_per_order', 7.5);

    $tenant->refresh();

    expect($tenant->name)->toBe('Novo Nome')
        ->and($tenant->delivery_cost_per_order)->toBe(7.5)
        ->and($tenant->opening_time)->toBe('10:00:00');
});

it('valida os dados antes de salvar as configurações', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();

    $this->putJson('/api/superadmin/tenants/'.$tenant->id.'/settings', [
        'name' => '',
        'email' => 'invalido',
        'opening_time' => '25:99',
        'delivery_cost_per_order' => -5,
        'delivery_radius' => 999,
    ])->assertStatus(422);

    $tenant->refresh();
    expect($tenant->name)->not->toBe('');
});

it('renderiza a página de configurações da empresa no painel', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant(['name' => 'Empresa Teste']);

    $this->get(route('superadmin.tenant.settings', $tenant))
        ->assertOk()
        ->assertSee('Configurações da Empresa');
});

it('usuário comum não acessa as configurações pelo painel superadmin', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);
    $this->actingAs($admin);

    $this->getJson('/api/superadmin/tenants/'.$tenant->id.'/settings')->assertForbidden();
    $this->get(route('superadmin.tenant.settings', $tenant))->assertForbidden();
});
