<?php

use App\Models\DeliveryPerson;
use App\Models\Order;
use Illuminate\Http\UploadedFile;

test('delivery order delivers with base64 photo', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $order = Order::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'delivery_person_id' => $delivery->id,
        'total' => 50.0,
        'delivery_cost' => 7.0,
        'status' => 'saiu_entrega',
        'type' => 'entrega',
        'accepted_at' => now()->subMinutes(30),
        'picked_up_at' => now()->subMinutes(10),
    ]);

    $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $dataUrl = 'data:image/jpeg;base64,'.base64_encode($pixel);

    $this->actingAs($delivery, 'delivery-web')
        ->post(route('delivery.order.deliver', $order->id), [
            'photo_data' => $dataUrl,
            'lat' => -23.55,
            'lng' => -46.63,
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->status)->toBe('entregue');
    expect($order->delivery_photo_path)->not->toBeNull();
    expect((float) $order->delivery_lat)->toBe(-23.55);
    expect((float) $order->delivery_lng)->toBe(-46.63);
    expect(Storage::disk('public')->exists($order->delivery_photo_path))->toBeTrue();
});

test('delivery order delivers with uploaded photo file', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $order = Order::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'delivery_person_id' => $delivery->id,
        'total' => 50.0,
        'delivery_cost' => 7.0,
        'status' => 'coletado',
        'type' => 'entrega',
        'accepted_at' => now()->subMinutes(30),
        'picked_up_at' => now()->subMinutes(10),
    ]);

    $this->actingAs($delivery, 'delivery-web')
        ->post(route('delivery.order.deliver', $order->id), [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->status)->toBe('entregue');
    expect($order->delivery_photo_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($order->delivery_photo_path))->toBeTrue();
});

test('delivery order does not deliver order from another delivery person', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $deliveryA = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca A',
        'phone' => '(11) 99999-9999',
        'status' => 'active',
    ]);

    $deliveryB = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Motoca B',
        'phone' => '(11) 88888-8888',
        'status' => 'active',
    ]);

    $order = Order::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'delivery_person_id' => $deliveryA->id,
        'total' => 50.0,
        'delivery_cost' => 7.0,
        'status' => 'saiu_entrega',
        'type' => 'entrega',
        'accepted_at' => now()->subMinutes(30),
        'picked_up_at' => now()->subMinutes(10),
    ]);

    $this->actingAs($deliveryB, 'delivery-web')
        ->post(route('delivery.order.deliver', $order->id), [])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('saiu_entrega');
});
