<?php

declare(strict_types=1);

use App\Livewire\Admin\DeliveryPeopleManager;
use App\Models\DeliveryPerson;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

test('legacy plain-text api_token still authenticates during transition (with deprecation log)', function () {
    $tenant = createTenant();
    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Legacy',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'api_token' => 'legacy-plain-token-123',
    ]);

    Log::spy();

    $this->withHeaders(['Authorization' => 'Bearer legacy-plain-token-123'])
        ->getJson('/api/delivery/profile')
        ->assertStatus(200);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'legacy plain-text api_token'));
});

test('new hashed api_token authenticates and is not stored in plain text', function () {
    $tenant = createTenant();
    $token = 'fresh-token-456';
    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Hashed',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'api_token' => DeliveryPerson::hashToken($token),
    ]);

    $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->getJson('/api/delivery/profile')
        ->assertStatus(200);

    expect($delivery->fresh()->api_token)
        ->toBe(DeliveryPerson::hashToken($token))
        ->not->toBe($token);
});

test('invalid api_token returns 401', function () {
    $tenant = createTenant();
    DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Nobody',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'api_token' => DeliveryPerson::hashToken('real-token-789'),
    ]);

    $this->withHeaders(['Authorization' => 'Bearer wrong-token'])
        ->getJson('/api/delivery/profile')
        ->assertStatus(401);
});

test('generateToken stores a sha256 hash, never the plain token', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Token Flow',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    Livewire::actingAs($admin)
        ->test(DeliveryPeopleManager::class)
        ->call('generateToken', $delivery->id);

    $stored = $delivery->fresh()->api_token;

    expect($stored)
        ->toMatch('/^[0-9a-f]{64}$/')
        ->not->toBe($delivery->fresh()->id);

    // A hash armazenado não funciona como token (busca por hash), o token cru
    // original não está no banco.
    expect(DeliveryPerson::where('api_token', $stored)->count())->toBe(1);
    expect(DeliveryPerson::where('api_token', DeliveryPerson::hashToken($stored))->count())->toBe(0);
});
