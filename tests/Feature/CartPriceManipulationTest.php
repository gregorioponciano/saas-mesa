<?php

declare(strict_types=1);

use App\Livewire\Admin\TableGrid;
use App\Livewire\Public\Cart;
use App\Livewire\Waiter\WaiterDashboard;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use App\Models\Tenant;
use App\Services\GeocodingService;
use Livewire\Livewire;

function createP0Tenant(): Tenant
{
    return createTenant([
        'opening_time' => '00:00',
        'closing_time' => '23:59',
        'address' => 'Rua Teste 123',
        'city' => 'Sao Paulo',
        'state' => 'SP',
        'latitude' => -21.6869,
        'longitude' => -49.7989,
        'delivery_radius' => 50,
        'delivery_cost_enabled' => true,
        'delivery_cost_per_order' => 4,
        'delivery_cost_per_km' => 2,
    ]);
}

function createP0Product(Tenant $tenant, float $price = 50.0): Product
{
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);

    return Product::factory()->create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'price' => $price,
        'stock' => 10,
        'status' => 'active',
    ]);
}

function mockGeocoding(): void
{
    app()->instance(GeocodingService::class, Mockery::mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('geocode')->andReturn(['lat' => -21.7769, 'lng' => -49.7989, 'display_name' => 'Teste']);
    }));
}

function deliveryDistanceKm(): float
{
    return GeocodingService::haversineDistance(-21.6869, -49.7989, -21.7769, -49.7989);
}

function firstCartItem(\Livewire\Features\SupportTesting\Testable $test): array
{
    $items = $test->get('cartItems');
    expect($items)->not->toBeEmpty();

    return (array) reset($items);
}

test('P0: preco manipulado no cardapio publico nao altera o total do pedido', function () {
    mockGeocoding();
    $tenant = createP0Tenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = createP0Product($tenant);

    Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, 0.01, [], 1)
        ->set('orderType', 'entrega')
        ->set('customerName', 'Cliente Teste')
        ->set('customerPhone', '(11) 99999-9999')
        ->set('deliveryAddress', 'Rua Teste 123')
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100)
        ->call('checkout');

    $order = Order::where('tenant_id', $tenant->id)->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total)->toBe(round(50.0 + $tenant->deliveryCostForDistance(deliveryDistanceKm()), 2));

    $item = $order->items()->first();
    expect((float) $item->price)->toBe(50.0);
});

test('P0: opcoes com preco adulterado usam o preco real do banco', function () {
    $tenant = createP0Tenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = createP0Product($tenant);

    $attribute = ProductAttribute::create([
        'tenant_id' => $tenant->id,
        'product_id' => $product->id,
        'name' => 'Tamanho',
        'type' => 'single',
        'price' => 3.00,
    ]);

    $option = ProductAttributeOption::create([
        'product_attribute_id' => $attribute->id,
        'name' => 'Grande',
        'price_additional' => 5.00,
    ]);

    $tamperedOptions = [[
        'attribute_id' => $attribute->id,
        'attribute_name' => 'Tamanho',
        'option_id' => $option->id,
        'option_name' => 'Grande',
        'price_additional' => 0.01,
        'attribute_price' => 0,
    ]];

    $test = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, 0.01, $tamperedOptions, 1);

    $item = firstCartItem($test);
    expect((float) $item['unit_price'])->toBe(58.0);
    expect((float) $item['options_total'])->toBe(5.0);
    expect((float) $item['attribute_price_total'])->toBe(3.0);
    expect($item['options'][0]['price_additional'])->toBe(5.0);
});

test('P0: opcao que nao pertence ao produto e ignorada silenciosamente', function () {
    $tenant = createP0Tenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = createP0Product($tenant);
    $otherProduct = createP0Product($tenant);

    $otherAttribute = ProductAttribute::create([
        'tenant_id' => $tenant->id,
        'product_id' => $otherProduct->id,
        'name' => 'Extra',
        'type' => 'single',
        'price' => 9.00,
    ]);

    $otherOption = ProductAttributeOption::create([
        'product_attribute_id' => $otherAttribute->id,
        'name' => 'Bacon',
        'price_additional' => 8.00,
    ]);

    $invalidOptions = [[
        'attribute_id' => $otherAttribute->id,
        'option_id' => $otherOption->id,
        'option_name' => 'Bacon',
        'price_additional' => 8.00,
        'attribute_price' => 9.00,
    ]];

    $test = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, 0.01, $invalidOptions, 1);

    $item = firstCartItem($test);
    expect((float) $item['unit_price'])->toBe(50.0);
    expect($item['options'])->toBe([]);
});

test('P0: checkout recalcula com o preco atual do banco mesmo apos mudanca no cardapio', function () {
    mockGeocoding();
    $tenant = createP0Tenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = createP0Product($tenant, 40.0);

    Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, 40.0, [], 1)
        ->set('orderType', 'entrega')
        ->set('customerName', 'Cliente Teste')
        ->set('customerPhone', '(11) 99999-9999')
        ->set('deliveryAddress', 'Rua Teste 123')
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100);

    $product->update(['price' => 60.0]);

    Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->set('orderType', 'entrega')
        ->set('customerName', 'Cliente Teste')
        ->set('customerPhone', '(11) 99999-9999')
        ->set('deliveryAddress', 'Rua Teste 123')
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100)
        ->call('checkout');

    $order = Order::where('tenant_id', $tenant->id)->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total)->toBe(round(60.0 + $tenant->deliveryCostForDistance(deliveryDistanceKm()), 2));
});

test('P0: item resgatado com pontos nao aceita custo em pontos vindo do cliente', function () {
    $tenant = createP0Tenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = createP0Product($tenant);
    $product->update(['points_price' => 100]);

    $test = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addRedeemedPointsItem', $product->id, $product->name, 1, 1);

    $item = firstCartItem($test);
    expect($item['points_cost'])->toBe(100);
    expect($item['is_points_item'])->toBeTrue();
    expect((float) $item['unit_price'])->toBe(0.0);
});

test('P0: painel admin (TableGrid) tambem usa o preco real do banco', function () {
    mockGeocoding();
    $tenant = createP0Tenant();
    $admin = createTenantAdmin($tenant);
    $product = createP0Product($tenant);

    Livewire::actingAs($admin)
        ->test(TableGrid::class)
        ->call('addToCart', $product->id, $product->name, 0.01, [], 1)
        ->set('orderType', 'entrega')
        ->set('orderPaymentMethod', 'cash')
        ->set('cashAmount', 100)
        ->set('customerName', 'Cliente Teste')
        ->set('deliveryAddress', 'Rua Teste 123')
        ->call('placeOrder');

    $order = Order::where('tenant_id', $tenant->id)->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total)->toBe(round(50.0 + $tenant->deliveryCostForDistance(deliveryDistanceKm()), 2));

    $item = $order->items()->first();
    expect((float) $item->price)->toBe(50.0);
});

test('P0: painel do garcom (WaiterDashboard) tambem usa o preco real do banco', function () {
    mockGeocoding();
    $tenant = createP0Tenant();
    $staff = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);
    $product = createP0Product($tenant);

    Livewire::actingAs($staff)
        ->test(WaiterDashboard::class)
        ->call('addToCart', $product->id, $product->name, 0.01, [], 1)
        ->set('orderType', 'entrega')
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 100)
        ->set('customerName', 'Cliente Teste')
        ->set('deliveryAddress', 'Rua Teste 123')
        ->call('placeOrder');

    $order = Order::where('tenant_id', $tenant->id)->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total)->toBe(round(50.0 + $tenant->deliveryCostForDistance(deliveryDistanceKm()), 2));

    $item = $order->items()->first();
    expect((float) $item->price)->toBe(50.0);
});
