<?php

use App\Livewire\Admin\DeliveryPeopleManager;
use Livewire\Livewire;

test('delivery people manager page renders', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    App\Models\DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Entregador Teste',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    Livewire::actingAs($user)
        ->test(DeliveryPeopleManager::class)
        ->assertSee('Entregador Teste');
});

test('delivery people page route loads via http', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    App\Models\DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get('/dashboard/entregadores')
        ->assertOk()
        ->assertSee('Motoca')
        ->assertSee('Entregadores');
});

test('show performance button loads delivery profile', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = App\Models\DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    App\Models\Order::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'delivery_person_id' => $delivery->id,
        'total' => 50.0,
        'delivery_cost' => 7.0,
        'status' => 'entregue',
        'type' => 'entrega',
        'accepted_at' => now()->subMinutes(30),
        'delivered_at' => now()->subMinutes(10),
    ]);

    Livewire::actingAs($user)
        ->test(DeliveryPeopleManager::class)
        ->call('openPerformance', $delivery->id)
        ->assertSet('showPerformance', true)
        ->assertSet('performanceData.total_deliveries', 1);
});
