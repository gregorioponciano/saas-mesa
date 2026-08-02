<?php

use App\Livewire\Admin\Settings;
use App\Livewire\Public\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\GeocodingService;
use Livewire\Livewire;

function createFeeTenant(array $overrides = []): Tenant
{
    return Tenant::factory()->create(array_merge([
        'slug' => 'fee-'.Str::random(8),
        'delivery_cost_per_order' => 10,
        'delivery_cost_per_km' => 5,
        'delivery_cost_enabled' => true,
    ], $overrides));
}

test('fee is the sum of fixed value plus per km times distance', function () {
    $tenant = createFeeTenant();

    expect($tenant->deliveryCostForDistance(2.0))->toBe(20.0);
    expect($tenant->deliveryCostForDistance(0.0))->toBe(10.0);
    expect($tenant->deliveryCostForDistance(null))->toBe(10.0);
});

test('fee uses only the fixed value when per km is zero', function () {
    $tenant = createFeeTenant(['delivery_cost_per_km' => 0]);

    expect($tenant->deliveryCostForDistance(3.0))->toBe(10.0);
});

test('fee uses only per km when fixed is zero', function () {
    $tenant = createFeeTenant(['delivery_cost_per_order' => 0]);

    expect($tenant->deliveryCostForDistance(2.0))->toBe(10.0);
});

test('delivery fee is zero when disabled', function () {
    $tenant = createFeeTenant(['delivery_cost_enabled' => false]);

    expect($tenant->deliveryCostForDistance(3.0))->toBe(0.0);
    expect($tenant->deliveryCostForDistance())->toBe(0.0);
});

test('settings saves fee values and toggle', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('deliveryCostPerOrder', '7.5')
        ->set('deliveryCostPerKm', '2.5')
        ->set('deliveryCostEnabled', false)
        ->call('saveTenant');

    $tenant->refresh();

    expect((float) $tenant->delivery_cost_per_order)->toBe(7.5);
    expect((float) $tenant->delivery_cost_per_km)->toBe(2.5);
    expect($tenant->delivery_cost_enabled)->toBe(false);
});

test('cart total adds fee based on real distance', function () {
    $tenant = createFeeTenant();
    $user = createTenantAdmin($tenant, ['phone' => '(11) 99999-9999', 'is_staff' => false]);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'price' => 14.9,
    ]);

    Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, (float) $product->price)
        ->set('orderType', 'entrega')
        ->set('deliveryDistance', 2.0)
        ->assertSet('total', 34.9)
        ->assertSeeHtml('Taxa de Entrega')
        ->assertSeeHtml('R$ 10,00 + R$ 5,00/km')
        ->assertSeeHtml('+R$ 20,00');
});

test('cart total has no fee when delivery fee is disabled', function () {
    $tenant = createFeeTenant(['delivery_cost_enabled' => false]);
    $user = createTenantAdmin($tenant, ['phone' => '(11) 99999-9999', 'is_staff' => false]);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'price' => 14.9,
    ]);

    Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, (float) $product->price)
        ->set('orderType', 'entrega')
        ->set('deliveryDistance', 2.0)
        ->assertSet('total', 14.9)
        ->assertDontSeeHtml('Taxa de Entrega');
});

test('cart checkout stores delivery cost as fixed plus per km', function () {
    $tenant = createFeeTenant([
        'delivery_cost_per_order' => 4,
        'delivery_cost_per_km' => 2,
        'address' => 'Rua Teste 123',
        'city' => 'Sao Paulo',
        'state' => 'SP',
        'latitude' => -21.6869,
        'longitude' => -49.7989,
        'delivery_radius' => 50,
        'opening_time' => '00:00:00',
        'closing_time' => '23:59:59',
    ]);
    $user = createTenantAdmin($tenant, ['phone' => '(11) 99999-9999', 'is_staff' => false]);

    app()->instance(GeocodingService::class, Mockery::mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('geocode')->andReturn(['lat' => -21.7769, 'lng' => -49.7989, 'display_name' => 'Teste']);
    }));

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'price' => 40.0,
    ]);

    Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, (float) $product->price)
        ->set('orderType', 'entrega')
        ->set('customerName', 'Cliente Teste')
        ->set('customerPhone', '(11) 99999-9999')
        ->set('deliveryAddress', 'Rua Teste 123')
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', '100')
        ->call('checkout');

    $order = Order::where('tenant_id', $tenant->id)->first();

    expect($order)->not->toBeNull();
    expect((float) $order->delivery_cost)->toBeGreaterThanOrEqual(24.0);
    expect((float) $order->delivery_cost)->toBeLessThan(24.1);
    expect((float) $order->total)->toEqual((float) round(40.0 + $order->delivery_cost, 2));
});
