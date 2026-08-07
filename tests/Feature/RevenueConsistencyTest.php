<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RevenueService;

function revenueService(): RevenueService
{
    return app(RevenueService::class);
}

test('faturamento conta apenas pagamentos efetivamente recebidos', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $paid = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 100.00,
        'status' => 'fechado',
        'type' => 'entrega',
    ]);
    Payment::create([
        'order_id' => $paid->id,
        'tenant_id' => $tenant->id,
        'amount' => 100.00,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $unpaid = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 50.00,
        'status' => 'entregue',
        'type' => 'entrega',
    ]);

    $revenue = revenueService()->revenue($tenant->id, RevenueService::PERIOD_ALL);

    expect((float) $revenue->total_revenue)->toBe(100.0);
    expect((float) $revenue->delivery_revenue)->toBe(100.0);
    expect((float) $revenue->table_revenue)->toBe(0.0);
});

test('pedido cancelado com pagamento estornado sai do faturamento', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 14.90,
        'status' => 'cancelado',
        'type' => 'entrega',
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 14.90,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now()->subMinutes(5),
    ]);

    $before = revenueService()->revenue($tenant->id, RevenueService::PERIOD_ALL);
    expect((float) $before->total_revenue)->toBe(14.9);

    $payment->markRefunded($client->id, 'Ressarcimento');

    $after = revenueService()->revenue($tenant->id, RevenueService::PERIOD_ALL);
    expect((float) $after->total_revenue)->toBe(0.0);
});

test('pedido reaberto pelo admin continua contando como pago ate cancelamento real', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 60.00,
        'status' => 'fechado',
        'type' => 'mesa',
    ]);
    Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 60.00,
        'payment_method' => 'cash',
        'status' => 'paid',
        'paid_at' => now()->subMinutes(10),
    ]);

    $order->update(['status' => 'entregue', 'bill_closed_at' => null]);

    $revenue = revenueService()->revenue($tenant->id, RevenueService::PERIOD_ALL);

    expect((float) $revenue->total_revenue)->toBe(60.0);
    expect((float) $revenue->table_revenue)->toBe(60.0);
});

test('faturamento do periodo usa data do pagamento (nao do pedido)', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 30.00,
        'status' => 'fechado',
        'type' => 'retirada',
        'created_at' => now()->subDays(10),
    ]);
    Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 30.00,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $today = revenueService()->revenue($tenant->id, RevenueService::PERIOD_TODAY);
    expect((float) $today->total_revenue)->toBe(30.0);

    $old = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 999.00,
        'status' => 'fechado',
        'type' => 'entrega',
        'created_at' => now()->subDays(10),
    ]);
    Payment::create([
        'order_id' => $old->id,
        'tenant_id' => $tenant->id,
        'amount' => 999.00,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now()->subDays(10),
    ]);

    $today = revenueService()->revenue($tenant->id, RevenueService::PERIOD_TODAY);
    expect((float) $today->total_revenue)->toBe(30.0);
});

test('cobranca EFI paga entra no faturamento e reembolsada sai', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 200.00,
        'status' => 'fechado',
        'type' => 'entrega',
    ]);

    $op = OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 20000,
        'method' => 'pix',
        'status' => 'paid',
        'idempotency_key' => 'rev-efi-1',
        'paid_at' => now(),
    ]);

    $revenue = revenueService()->revenue($tenant->id, RevenueService::PERIOD_ALL);
    expect((float) $revenue->total_revenue)->toBe(200.0);

    $op->update(['status' => 'refunded']);

    $revenue = revenueService()->revenue($tenant->id, RevenueService::PERIOD_ALL);
    expect((float) $revenue->total_revenue)->toBe(0.0);
});

test('grafico de receita agrupa por dia com base no pagamento', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID]);
    $client = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLIENTE]);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $client->id,
        'total' => 45.00,
        'status' => 'fechado',
        'type' => 'entrega',
    ]);
    Payment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount' => 45.00,
        'payment_method' => 'pix',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $series = revenueService()->revenueChart($tenant->id, 7);

    $today = now()->format('Y-m-d');
    expect($series)->toHaveCount(7);
    expect((float) $series[$today])->toBe(45.0);
});
