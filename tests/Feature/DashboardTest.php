<?php

use App\Models\Tenant;
use App\Models\User;

test('dashboard requer autenticacao', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('dashboard e acessivel para usuario autenticado', function () {
    $tenant = Tenant::factory()->create(['plan' => Tenant::PLAN_FREE]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
});

test('pagina de gerenciar mesas requer autenticacao', function () {
    $response = $this->get('/dashboard/tables');
    $response->assertRedirect('/login');
});

test('pagina de gerenciar mesas e acessivel para usuario autenticado', function () {
    $tenant = Tenant::factory()->create(['plan' => Tenant::PLAN_FREE]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->get('/dashboard/tables');
    $response->assertStatus(200);
});
