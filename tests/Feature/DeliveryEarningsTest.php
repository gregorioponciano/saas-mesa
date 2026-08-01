<?php

use App\Livewire\Admin\DeliveryPeopleManager;
use App\Models\DeliveryEarning;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Services\DeliveryService;
use Livewire\Livewire;

function makeDeliveredOrder($delivery, $user, array $overrides = []): Order
{
    return Order::create(array_merge([
        'tenant_id' => $delivery->tenant_id,
        'user_id' => $user->id,
        'delivery_person_id' => $delivery->id,
        'total' => 50.0,
        'delivery_cost' => 7.0,
        'status' => 'saiu_entrega',
        'type' => 'entrega',
        'accepted_at' => now()->subMinutes(30),
        'picked_up_at' => now()->subMinutes(10),
    ], $overrides));
}

function deliverOrderViaHttp($delivery, $order): void
{
    $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    test()->actingAs($delivery, 'delivery-web')
        ->post(route('delivery.order.deliver', $order->id), [
            'photo_data' => 'data:image/jpeg;base64,' . base64_encode($pixel),
        ])
        ->assertRedirect();
}

test('earning is created as pending when order is delivered', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $order = makeDeliveredOrder($delivery, $user);
    deliverOrderViaHttp($delivery, $order);

    $earning = DeliveryEarning::where('order_id', $order->id)->first();

    expect($earning)->not->toBeNull();
    expect($earning->amount)->toEqual('7.00');
    expect($earning->status)->toBe(DeliveryEarning::STATUS_PENDING);
    expect($earning->paid_at)->toBeNull();
    expect($earning->earned_at)->not->toBeNull();
});

test('earning is not created when delivery cost is zero', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $order = makeDeliveredOrder($delivery, $user, ['delivery_cost' => 0]);
    deliverOrderViaHttp($delivery, $order);

    expect(DeliveryEarning::where('order_id', $order->id)->exists())->toBeFalse();
});

test('earning is not duplicated when delivery is confirmed twice', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $order = makeDeliveredOrder($delivery, $user);
    deliverOrderViaHttp($delivery, $order);

    $earning = DeliveryEarning::where('order_id', $order->id)->firstOrFail();
    $earning->update(['status' => DeliveryEarning::STATUS_PAID, 'paid_at' => now()]);

    $this->actingAs($delivery, 'delivery-web')
        ->post(route('delivery.order.deliver', $order->id), [])
        ->assertRedirect();

    expect(DeliveryEarning::where('order_id', $order->id)->count())->toBe(1);
    expect($earning->fresh()->status)->toBe(DeliveryEarning::STATUS_PAID);
});

test('admin can mark a single earning as paid', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $order = makeDeliveredOrder($delivery, $user, ['status' => 'entregue', 'delivered_at' => now()]);
    $earning = DeliveryEarning::create([
        'tenant_id' => $tenant->id,
        'delivery_person_id' => $delivery->id,
        'order_id' => $order->id,
        'amount' => 7.0,
        'status' => DeliveryEarning::STATUS_PENDING,
        'earned_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(DeliveryPeopleManager::class)
        ->call('openPerformance', $delivery->id)
        ->call('markEarningPaid', $earning->id)
        ->assertHasNoErrors();

    expect($earning->fresh()->status)->toBe(DeliveryEarning::STATUS_PAID);
    expect($earning->fresh()->paid_at)->not->toBeNull();
});

test('admin can mark all pending earnings as paid', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    foreach ([7.0, 9.5, 0] as $i => $cost) {
        $order = makeDeliveredOrder($delivery, $user, [
            'status' => 'entregue',
            'delivered_at' => now(),
            'delivery_cost' => $cost,
        ]);

        if ($cost > 0) {
            DeliveryEarning::create([
                'tenant_id' => $tenant->id,
                'delivery_person_id' => $delivery->id,
                'order_id' => $order->id,
                'amount' => $cost,
                'status' => DeliveryEarning::STATUS_PENDING,
                'earned_at' => now(),
            ]);
        }
    }

    Livewire::actingAs($user)
        ->test(DeliveryPeopleManager::class)
        ->call('openPerformance', $delivery->id)
        ->call('markAllEarningsPaid');

    expect(DeliveryEarning::where('delivery_person_id', $delivery->id)->where('status', 'pending')->count())->toBe(0);
    expect(DeliveryEarning::where('delivery_person_id', $delivery->id)->where('status', 'paid')->count())->toBe(2);
});

test('earnings summary splits pending and paid totals', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    foreach ([['amount' => 7.0, 'status' => 'pending'], ['amount' => 10.0, 'status' => 'paid'], ['amount' => 5.5, 'status' => 'paid']] as $data) {
        $order = makeDeliveredOrder($delivery, $user, ['status' => 'entregue', 'delivered_at' => now()]);
        DeliveryEarning::create(array_merge([
            'tenant_id' => $tenant->id,
            'delivery_person_id' => $delivery->id,
            'order_id' => $order->id,
            'earned_at' => now(),
        ], $data));
    }

    $summary = app(DeliveryService::class)->getEarningsSummary($delivery);

    expect($summary['total'])->toBe(22.5);
    expect($summary['pending'])->toBe(7.0);
    expect($summary['paid'])->toBe(15.5);
    expect($summary['pending_count'])->toBe(1);
    expect($summary['paid_count'])->toBe(2);
});

test('daily history groups earnings by day with pending and paid amounts', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    foreach ([
        ['amount' => 7.0, 'status' => 'pending', 'days_ago' => 0],
        ['amount' => 8.0, 'status' => 'paid', 'days_ago' => 0],
        ['amount' => 12.0, 'status' => 'pending', 'days_ago' => 2],
    ] as $data) {
        $order = makeDeliveredOrder($delivery, $user, ['status' => 'entregue', 'delivered_at' => now()->subDays($data['days_ago'])]);
        DeliveryEarning::create([
            'tenant_id' => $tenant->id,
            'delivery_person_id' => $delivery->id,
            'order_id' => $order->id,
            'amount' => $data['amount'],
            'status' => $data['status'],
            'earned_at' => now()->subDays($data['days_ago']),
        ]);
    }

    $history = app(DeliveryService::class)->getEarningsDailyHistory($delivery, now()->subDays(5)->toDateTimeString(), now()->toDateTimeString());

    expect($history)->toHaveCount(2);
    expect($history[0]['date'])->toBe(now()->format('Y-m-d'));
    expect($history[0]['total'])->toBe(15.0);
    expect($history[0]['pending'])->toBe(7.0);
    expect($history[0]['paid'])->toBe(8.0);
    expect($history[1]['date'])->toBe(now()->subDays(2)->format('Y-m-d'));
    expect($history[1]['total'])->toBe(12.0);
});

test('delivery dashboard shows earnings history', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
        'email' => 'motoca@localhost.dev',
        'cpf' => '12345678900',
        'vehicle_plate' => 'ABC-1234',
        'vehicle_model' => 'Moto',
        'password' => bcrypt('secret123'),
        'activated_at' => now(),
    ]);

    $order = makeDeliveredOrder($delivery, $user, ['status' => 'entregue', 'delivered_at' => now()]);
    DeliveryEarning::create([
        'tenant_id' => $tenant->id,
        'delivery_person_id' => $delivery->id,
        'order_id' => $order->id,
        'amount' => 7.0,
        'status' => DeliveryEarning::STATUS_PENDING,
        'earned_at' => now(),
    ]);

    $this->actingAs($delivery, 'delivery-web')
        ->get('/entregador/painel')
        ->assertOk()
        ->assertSee('Histórico Diário')
        ->assertSee('Ganhos por Pedido')
        ->assertSee('Pendente');
});
