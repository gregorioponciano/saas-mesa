<?php

declare(strict_types=1);

use App\Models\CustomerPoint;
use App\Models\LoyaltyConfig;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OrderLifecycleService;
use App\Services\PointsService;
use App\Services\StockService;

function lifecycleService(): OrderLifecycleService
{
    return app(OrderLifecycleService::class);
}

function makeOrder(Tenant $tenant, User $client, array $overrides = []): Order
{
    return Order::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 80.00,
        'status' => 'novo',
        'type' => 'mesa',
    ], $overrides));
}

function makeItem(Order $order, Product $product, int $quantity = 1): OrderItem
{
    return OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => $quantity,
        'price' => $product->price,
    ]);
}

test('cliente nao pode cancelar pedido de outro cliente', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $clientA = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);
    $clientB = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = makeOrder($tenant, $clientA);

    $result = lifecycleService()->cancelOrder(
        $order,
        $clientB,
        OrderLifecycleService::ACTOR_CLIENT,
        'teste'
    );

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('outro cliente');
});

test('cliente nao pode cancelar pedido fora da janela', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = makeOrder($tenant, $client);
    $order->forceFill(['created_at' => now()->subMinutes(Order::CLIENT_CANCELLATION_WINDOW_MINUTES + 1)])->save();

    $result = lifecycleService()->cancelOrder($order, $client, OrderLifecycleService::ACTOR_CLIENT);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Janela de cancelamento');
});

test('cliente dentro da janela cancela pedido proprio sem pagamento', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'stock' => 10]);

    $order = makeOrder($tenant, $client);
    makeItem($order, $product);

    $result = lifecycleService()->cancelOrder($order, $client, OrderLifecycleService::ACTOR_CLIENT, 'Nao quero mais');

    expect($result['success'])->toBeTrue();
    expect($order->fresh()->status)->toBe('cancelado');
    expect($order->fresh()->cancelled_by)->toBe($client->id);
    expect($order->fresh()->cancellation_reason)->toBe('Nao quero mais');
});

test('cliente nao pode cancelar pedido ja pago', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = makeOrder($tenant, $client);
    Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 80.00,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $result = lifecycleService()->cancelOrder($order, $client, OrderLifecycleService::ACTOR_CLIENT);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('apenas administradores');
});

test('atendente nao pode cancelar pedido pago, apenas admin; pagamento e estornado', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $admin = createTenantAdmin($tenant);
    $attendee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ATENDENTE]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = makeOrder($tenant, $client);
    Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 80.00,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $refused = lifecycleService()->cancelOrder($order, $attendee, OrderLifecycleService::ACTOR_ATTENDANT);
    expect($refused['success'])->toBeFalse();

    $result = lifecycleService()->cancelOrder($order, $admin, OrderLifecycleService::ACTOR_ADMIN, 'Erro do restaurante');
    expect($result['success'])->toBeTrue();
    expect($result['refunded_amount'])->toBe(80.0);
    expect($order->fresh()->status)->toBe('cancelado');
    expect($order->fresh()->cancelled_by)->toBe($admin->id);
    expect($order->fresh()->cancellation_reason)->toBe('Erro do restaurante');

    $payment = $order->fresh()->payments->first();
    expect($payment->status)->toBe('refunded');
    expect($payment->refunded_by)->toBe($admin->id);
    expect($payment->refunded_at)->not->toBeNull();
});

test('cancelamento admin marca cobranca EFI como reembolsada', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $admin = createTenantAdmin($tenant);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = makeOrder($tenant, $client, ['type' => 'entrega']);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 8000,
        'method' => 'pix',
        'status' => 'paid',
        'idempotency_key' => 'lifecycle-efi-1',
        'paid_at' => now(),
    ]);

    $result = lifecycleService()->cancelOrder($order, $admin, OrderLifecycleService::ACTOR_ADMIN);

    expect($result['success'])->toBeTrue();
    expect($result['refunded_amount'])->toBe(80.0);
    expect($order->fresh()->orderPayments->first()->status)->toBe('refunded');
});

test('reabertura de conta exige administrador', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $attendee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ATENDENTE]);
    $admin = createTenantAdmin($tenant);

    $order = makeOrder($tenant, $admin, ['status' => 'fechado', 'bill_closed_at' => now()]);

    $blocked = lifecycleService()->reopenAccount($order, $attendee);
    expect($blocked['success'])->toBeFalse();

    $result = lifecycleService()->reopenAccount($order, $admin);
    expect($result['success'])->toBeTrue();
    expect($order->fresh()->status)->toBe('entregue');
    expect($order->fresh()->bill_closed_at)->toBeNull();
});

test('reabertura de conta ocupa mesa novamente', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $admin = createTenantAdmin($tenant);
    $table = Table::factory()->create(['tenant_id' => $tenant->id, 'status' => 'free']);

    $order = makeOrder($tenant, $admin, ['status' => 'fechado', 'bill_closed_at' => now(), 'table_id' => $table->id]);

    $result = lifecycleService()->reopenAccount($order, $admin);

    expect($result['success'])->toBeTrue();
    expect($order->fresh()->table->status)->toBe('occupied');
});

test('reabertura estorna pontos e novo fechamento pode conceder novamente', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    LoyaltyConfig::forTenant($tenant)->update(['points_enabled' => true, 'points_percentage' => 1]);
    $admin = createTenantAdmin($tenant);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = makeOrder($tenant, $client, ['total' => 100.00, 'status' => 'fechado', 'bill_closed_at' => now()]);

    app(PointsService::class)->grantPointsForOrder($order);
    expect(CustomerPoint::getBalance($tenant, $client))->toBe(100);

    $result = lifecycleService()->reopenAccount($order, $admin);
    expect($result['success'])->toBeTrue();
    expect(CustomerPoint::getBalance($tenant, $client))->toBe(0);

    $order->update(['status' => 'fechado', 'bill_closed_at' => now()]);
    app(PointsService::class)->grantPointsForOrder($order->fresh());
    expect(CustomerPoint::getBalance($tenant, $client))->toBe(100);
});

test('cancela item devolve estoque, reduz total e cancela pedido sem itens', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $admin = createTenantAdmin($tenant);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'stock' => 10, 'price' => 80.00]);

    $order = makeOrder($tenant, $client, ['total' => 80.00]);
    makeItem($order, $product);

    app(StockService::class)->deductStock($product->id, 1, $tenant->id, $order->id, null, 'sale', 'test');
    $product->refresh();
    expect($product->stock)->toBe(9);

    $result = lifecycleService()->cancelItem($order->items->first(), $admin, OrderLifecycleService::ACTOR_ADMIN);

    expect($result['success'])->toBeTrue();
    expect((float) $order->fresh()->total)->toBe(0.0);
    expect($order->fresh()->status)->toBe('cancelado');

    $product->refresh();
    expect($product->stock)->toBe(10);

    $returnedMovement = StockMovement::where('product_id', $product->id)
        ->where('order_id', $order->id)
        ->where('type', 'cancellation')
        ->first();
    expect($returnedMovement)->not->toBeNull();
    expect($returnedMovement->quantity)->toBe(1);
});

test('atendente nao pode cancelar item de conta fechada', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $attendee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ATENDENTE]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'stock' => 5]);

    $order = makeOrder($tenant, $client, ['status' => 'fechado', 'bill_closed_at' => now()]);
    makeItem($order, $product);

    $result = lifecycleService()->cancelItem($order->items->first(), $attendee, OrderLifecycleService::ACTOR_ATTENDANT);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('apenas administradores');
});

test('item bonificado (cortesia) sai do estoque com tipo bonificacao e e restituido no cancelamento', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $admin = createTenantAdmin($tenant);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'stock' => 10, 'price' => 25.00]);

    $order = makeOrder($tenant, $client, ['total' => 25.00]);
    makeItem($order, $product);
    $bonus = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 0.00,
        'is_bonificacao' => true,
        'bonificacao_reason' => 'Compensacao por erro na entrega',
    ]);

    app(StockService::class)->deductStock($product->id, 1, $tenant->id, $order->id, $admin->id, 'sale', 'Venda', $order->items->first()->id);
    app(StockService::class)->deductStock($product->id, 1, $tenant->id, $order->id, $admin->id, 'bonificacao', 'Bonificacao', $bonus->id);

    $product->refresh();
    expect($product->stock)->toBe(8);

    $bonusMovement = StockMovement::where('product_id', $product->id)
        ->where('type', 'bonificacao')
        ->first();
    expect($bonusMovement)->not->toBeNull();
    expect($bonusMovement->quantity)->toBe(-1);

    $result = lifecycleService()->cancelItem($bonus->fresh(), $admin, OrderLifecycleService::ACTOR_ADMIN);

    expect($result['success'])->toBeTrue();
    $product->refresh();
    expect($product->stock)->toBe(9);
    expect((float) $order->fresh()->total)->toBe(25.0);
});

test('duas linhas do mesmo produto tem baixa e devolucao independentes (estoque verdadeiro)', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $admin = createTenantAdmin($tenant);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'stock' => 10, 'price' => 20.00]);

    $order = makeOrder($tenant, $client, ['total' => 40.00]);
    $itemA = makeItem($order, $product);
    $itemB = makeItem($order, $product);

    app(StockService::class)->deductOrderStock($order, $admin->id);
    $product->refresh();
    expect($product->stock)->toBe(8);

    expect(StockMovement::where('product_id', $product->id)->where('type', 'sale')->count())->toBe(2);

    lifecycleService()->cancelItem($itemA, $admin, OrderLifecycleService::ACTOR_ADMIN);
    $product->refresh();
    expect($product->stock)->toBe(9);

    lifecycleService()->cancelItem($itemB->fresh(), $admin, OrderLifecycleService::ACTOR_ADMIN);
    $product->refresh();
    expect($product->stock)->toBe(10);

    expect(StockMovement::where('product_id', $product->id)->where('type', 'cancellation')->count())->toBe(2);
});
