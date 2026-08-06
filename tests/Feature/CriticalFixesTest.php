<?php

declare(strict_types=1);

use App\Livewire\Admin\TableGrid;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Table;
use App\Models\Tenant;
use App\Services\DeliveryService;

it('soma pagamentos EFI (order_payments) no paidAmount do pedido', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'type' => 'entrega',
        'total' => 150.00,
        'status' => 'em_preparo',
    ]);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 15000,
        'method' => 'pix',
        'status' => 'paid',
        'idempotency_key' => 'test-efi-1',
        'paid_at' => now(),
    ]);

    expect($order->fresh()->hasPayment())->toBeTrue();
    expect($order->fresh()->paidAmount())->toBe(150.0);
    expect($order->fresh()->pendingPaymentAmount())->toBe(0.0);
});

it('soma pagamento manual (payments) e EFI juntos no pending', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'total' => 200.00,
        'status' => 'novo',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 50.00,
        'payment_method' => 'cash',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 5000,
        'method' => 'pix',
        'status' => 'paid',
        'idempotency_key' => 'test-efi-2',
        'paid_at' => now(),
    ]);

    expect($order->fresh()->paidAmount())->toBe(100.0);
    expect($order->fresh()->pendingPaymentAmount())->toBe(100.0);
});

it('pedido sem pagamento tem pending igual ao total e hasPayment falso', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'total' => 80.00,
    ]);

    expect($order->hasPayment())->toBeFalse();
    expect($order->pendingPaymentAmount())->toBe(80.0);
});

it('ordena a coluna change_requested_at e persiste na troca', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'total' => 30.00,
        'status' => 'novo',
    ]);
    $item = OrderItem::create([
        'order_id' => $order->id,
        'product_name' => 'Hambúrguer',
        'quantity' => 1,
        'price' => 30.00,
        'change_requested' => false,
    ]);

    $item->update([
        'change_requested' => true,
        'change_requested_at' => now(),
        'change_note' => 'Trocar para sem cebola',
    ]);

    $fresh = $item->fresh();
    expect($fresh->change_requested)->toBeTrue();
    expect($fresh->change_requested_at)->not->toBeNull();
    expect($fresh->change_note)->toBe('Trocar para sem cebola');
    expect($fresh->canRequestChange())->toBeFalse();
});

it('pedido de entrega pago por PIX fica visivel para entregadores', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'type' => 'entrega',
        'status' => 'novo',
        'payment_method' => 'pix',
        'payment_status' => 'paid',
        'total' => 45.00,
    ]);

    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Entregador Teste',
        'phone' => '(11) 91111-1111',
        'status' => 'active',
    ]);

    $available = app(DeliveryService::class)->getAvailableOrders($delivery);

    expect($available->pluck('id'))->toContain($order->id);
});

it('freeTable fecha apenas pedidos totalmente pagos', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $table = Table::factory()->create(['tenant_id' => $tenant->id, 'status' => 'occupied']);

    $paidOrder = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'table_id' => $table->id,
        'total' => 100.00,
        'status' => 'entregue',
    ]);
    Payment::create([
        'order_id' => $paidOrder->id,
        'tenant_id' => $tenant->id,
        'amount' => 100.00,
        'payment_method' => 'cash',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $unpaidOrder = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'table_id' => $table->id,
        'total' => 50.00,
        'status' => 'entregue',
    ]);

    $this->actingAs(createTenantAdmin($tenant));

    $component = new TableGrid;
    $component->freeTable($table->id);

    expect($paidOrder->fresh()->status)->toBe('fechado');
    expect($paidOrder->fresh()->bill_closed_at)->not->toBeNull();
    expect($unpaidOrder->fresh()->status)->toBe('entregue');
    expect($table->fresh()->status)->toBe('free');
});
