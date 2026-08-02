<?php

declare(strict_types=1);

use App\Livewire\Admin\DeliveryPeopleManager;
use App\Models\DeliveryPerson;
use Livewire\Livewire;

test('isValidCpf validates real CPFs only', function () {
    expect(isValidCpf('102.935.779-00'))->toBeTrue();
    expect(isValidCpf('529.982.247-25'))->toBeTrue();
    expect(isValidCpf('39053344705'))->toBeTrue();
    expect(isValidCpf('111.111.111-11'))->toBeFalse();
    expect(isValidCpf('123.456.789-00'))->toBeFalse();
    expect(isValidCpf('102.935.779-01'))->toBeFalse();
    expect(isValidCpf('12345'))->toBeFalse();
    expect(isValidCpf(''))->toBeFalse();
    expect(isValidCpf(null))->toBeFalse();
});

test('maskCpf formats digits with dots and dash', function () {
    expect(maskCpf('39053344705'))->toBe('390.533.447-05');
    expect(maskCpf('390533447'))->toBe('390.533.447');
    expect(maskCpf('390533'))->toBe('390.533');
    expect(maskCpf('111.111.111-11'))->toBe('111.111.111-11');
    expect(maskCpf(''))->toBe('');
});

test('web invite rejects invalid CPF', function () {
    $tenant = createTenant();
    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'invite_token' => 'token_invite_inv',
        'invite_expires_at' => now()->addDay(),
    ]);

    $response = $this->post(route('delivery.invite.accept', $delivery->invite_token), [
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'email' => 'motoca@test.com',
        'cpf' => '111.111.111-11',
        'cnh' => '12345678901',
        'vehicle_plate' => 'ABC-1234',
        'vehicle_model' => 'Honda CG 160',
    ]);

    $response->assertSessionHasErrors('cpf');
    expect($delivery->fresh()->cpf)->toBeNull();
});

test('web invite accepts valid CPF and stores digits only', function () {
    $tenant = createTenant();
    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'invite_token' => 'token_invite_ok',
        'invite_expires_at' => now()->addDay(),
    ]);

    $response = $this->post(route('delivery.invite.accept', $delivery->invite_token), [
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'email' => 'motoca@test.com',
        'cpf' => '102.935.779-00',
        'cnh' => '12345678901',
        'vehicle_plate' => 'ABC-1234',
        'vehicle_model' => 'Honda CG 160',
    ]);

    $response->assertRedirect(route('delivery.dashboard'));
    expect($delivery->fresh()->cpf)->toBe('10293577900');
});

test('api invite rejects invalid CPF with 422', function () {
    $tenant = createTenant();
    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'invite_token' => 'token_api_inv',
        'invite_expires_at' => now()->addDay(),
    ]);

    $response = $this->postJson("/api/delivery/invitation/{$delivery->invite_token}", [
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '123.456.789-00',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('cpf');
    expect($delivery->fresh()->cpf)->toBeNull();
});

test('api invite accepts valid CPF and stores digits only', function () {
    $tenant = createTenant();
    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'invite_token' => 'token_api_ok',
        'invite_expires_at' => now()->addDay(),
    ]);

    $response = $this->postJson("/api/delivery/invitation/{$delivery->invite_token}", [
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '102.935.779-00',
    ]);

    $response->assertStatus(200);
    expect($delivery->fresh()->cpf)->toBe('10293577900');
});

test('admin delivery form rejects invalid CPF', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    Livewire::actingAs($user)
        ->test(DeliveryPeopleManager::class)
        ->set('showModal', true)
        ->set('name', 'Motoca')
        ->set('phone', '(11) 99999-9999')
        ->set('cpf', '11111111111')
        ->call('save')
        ->assertHasErrors(['cpf']);
});

test('admin delivery form accepts valid CPF and stores digits only', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    Livewire::actingAs($user)
        ->test(DeliveryPeopleManager::class)
        ->set('showModal', true)
        ->set('name', 'Motoca')
        ->set('phone', '(11) 99999-9999')
        ->set('cpf', '10293577900')
        ->call('save')
        ->assertHasNoErrors();

    $delivery = DeliveryPerson::where('tenant_id', $tenant->id)->first();
    expect($delivery)->not->toBeNull();
    expect($delivery->cpf)->toBe('10293577900');
});
