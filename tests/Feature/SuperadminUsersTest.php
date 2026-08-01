<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;

it('lista usuários da plataforma com filtros', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant();
    createTenantAdmin($tenant);
    createTenantAdmin($tenant, ['role' => 'atendente']);

    $this->getJson('/api/superadmin/users')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.role', 'superadmin');

    $this->getJson('/api/superadmin/users?role=admin')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('cria um novo superadmin', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/users', [
        'name' => 'Novo Super',
        'email' => 'novo@super.com',
        'password' => 'senha-segura-123',
    ])->assertCreated()
        ->assertJsonPath('user.role', 'superadmin');

    $user = User::where('email', 'novo@super.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->isSuperAdmin())->toBeTrue()
        ->and($user->tenant_id)->toBeNull();

    expect(AuditLog::where('action', 'user.create_superadmin')->exists())->toBeTrue();
});

it('valida dados ao criar superadmin', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/users', [
        'name' => '',
        'email' => $superadmin->email,
        'password' => 'curta',
    ])->assertStatus(422);
});

it('revoga acesso de um superadmin', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $other = createSuperAdmin();

    $this->postJson('/api/superadmin/users/'.$other->id.'/revoke')
        ->assertOk();

    $other->refresh();

    expect($other->role)->toBe('cliente')
        ->and($other->is_staff)->toBeFalse();

    expect(AuditLog::where('action', 'user.revoke_superadmin')->exists())->toBeTrue();
});

it('não permite revogar o próprio acesso', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/users/'.$superadmin->id.'/revoke')
        ->assertStatus(422);
});

it('não permite revogar o último superadmin da plataforma', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/users/'.$superadmin->id.'/revoke')
        ->assertStatus(422);

    expect($superadmin->refresh()->role)->toBe('superadmin');
});

it('renderiza a página de usuários do painel', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.users'))
        ->assertOk()
        ->assertSee('Usuários da Plataforma');
});
