<?php

use App\Models\Tenant;
use App\Models\User;

test('pagina de login e acessivel', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});

test('usuario pode fazer login com credenciais validas', function () {
    $tenant = Tenant::factory()->create(['plan' => Tenant::PLAN_FREE]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

test('usuario nao pode fazer login com senha invalida', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('pagina de registro e acessivel', function () {
    $response = $this->get('/register');
    $response->assertStatus(200);
});

test('usuario pode registrar novo tenant com mesas automaticas', function () {
    $response = $this->post('/register', [
        'tenant_name' => 'Nova Lanchonete',
        'tenant_email' => 'contato@novalanchonete.com',
        'slug' => 'nova-lanchonete',
        'name' => 'Joao',
        'email' => 'joao@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();

    $this->assertDatabaseHas('tenants', [
        'name' => 'Nova Lanchonete',
        'email' => 'contato@novalanchonete.com',
        'slug' => 'nova-lanchonete',
        'plan' => Tenant::PLAN_FREE,
        'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'joao@example.com',
        'role' => 'admin',
    ]);

    $tenant = Tenant::where('slug', 'nova-lanchonete')->first();
    expect($tenant->tables()->count())->toBe(Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE]);
});

test('slug deve ser unico', function () {
    $tenant = Tenant::factory()->create(['slug' => 'mesmo-slug']);

    $response = $this->post('/register', [
        'tenant_name' => 'Outra Lanchonete',
        'slug' => 'mesmo-slug',
        'name' => 'Maria',
        'email' => 'maria@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('slug');
});

test('usuario pode fazer logout', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user);
    $response = $this->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
