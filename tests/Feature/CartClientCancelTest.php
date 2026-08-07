<?php

declare(strict_types=1);

use App\Livewire\Public\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

function cartTestTenant(): Tenant
{
    return createTenant([
        'opening_time' => '00:00',
        'closing_time' => '23:59',
        'address' => 'Rua Teste 123',
        'city' => 'Sao Paulo',
        'state' => 'SP',
    ]);
}

function cartTestClient(Tenant $tenant): User
{
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => User::ROLE_CLIENTE,
        'is_staff' => false,
    ]);
}

function makeCartOrder(Tenant $tenant, User $client, Product $product, array $overrides = []): Order
{
    $order = Order::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => $product->price,
        'status' => 'novo',
        'type' => 'mesa',
        'customer_name' => $client->name,
    ], $overrides));

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => $product->price,
    ]);

    return $order;
}

function bindCartSession(Tenant $tenant, int $orderId): void
{
    Session::put("last_order_{$tenant->id}", $orderId);
}

test('cliente pode cancelar pedido proprio pelo carrinho dentro da janela', function () {
    $tenant = cartTestTenant();
    $user = cartTestClient($tenant);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'price' => 30.00, 'stock' => 5]);
    $order = makeCartOrder($tenant, $user, $product);
    bindCartSession($tenant, $order->id);

    $comp = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('loadOrderTracking');

    expect($comp->get('orderTracking')['can_cancel'])->toBeTrue();

    $comp->call('cancelTrackingOrder');

    expect($order->fresh()->status)->toBe('cancelado');
    expect($order->fresh()->cancellation_reason)->toContain('Cancelamento pelo cliente');
    expect($comp->get('orderTracking')['status'])->toBe('cancelado');
    expect($comp->get('orderTracking')['can_cancel'])->toBeFalse();
});

test('cliente nao pode cancelar pedido do carrinho fora da janela', function () {
    $tenant = cartTestTenant();
    $user = cartTestClient($tenant);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'price' => 30.00, 'stock' => 5]);
    $order = makeCartOrder($tenant, $user, $product);
    $order->forceFill(['created_at' => now()->subMinutes(Order::CLIENT_CANCELLATION_WINDOW_MINUTES + 1)])->save();
    bindCartSession($tenant, $order->id);

    $comp = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('loadOrderTracking');

    expect($comp->get('orderTracking')['can_cancel'])->toBeFalse();

    $comp->call('cancelTrackingOrder');

    expect($order->fresh()->status)->not->toBe('cancelado');
});

test('cliente nao pode cancelar pedido pago pelo carrinho', function () {
    $tenant = cartTestTenant();
    $user = cartTestClient($tenant);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'price' => 30.00, 'stock' => 5]);
    $order = makeCartOrder($tenant, $user, $product);

    Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 30.00,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    bindCartSession($tenant, $order->id);

    $comp = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('loadOrderTracking');

    expect($comp->get('orderTracking')['can_cancel'])->toBeFalse();

    $comp->call('cancelTrackingOrder');

    expect($order->fresh()->status)->toBe('novo');
    expect($comp->get('orderTracking')['can_cancel'])->toBeFalse();
});
