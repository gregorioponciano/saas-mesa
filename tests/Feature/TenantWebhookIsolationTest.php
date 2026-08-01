<?php

declare(strict_types=1);

use App\Events\OrderPaid;
use App\Jobs\ProcessEfiBankWebhook;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('webhook for txid of tenant A received on tenant B endpoint does not mark payment as paid', function () {
    Event::fake([OrderPaid::class]);

    $tenantA = createTenant();
    $tenantB = createTenant();
    $userA = createTenantAdmin($tenantA);

    $orderA = Order::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $userA->id,
        'total' => 100.00,
        'payment_status' => 'pending',
    ]);

    $paymentA = OrderPayment::create([
        'order_id' => $orderA->id,
        'tenant_id' => $tenantA->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'efi_pix_txid' => 'txid_belongs_to_tenant_a',
        'idempotency_key' => Str::uuid()->toString(),
    ]);

    // Webhook log "received on tenant B endpoint" with tenant B's id.
    $log = WebhookLog::create([
        'source' => 'tenant',
        'tenant_id' => $tenantB->id,
        'payload_json' => json_encode(['pix' => [['txid' => 'txid_belongs_to_tenant_a']]]),
        'signature' => 'fake-signature',
        'is_valid' => true,
        'processed' => false,
    ]);

    (new ProcessEfiBankWebhook($log->id, 'tenant', $tenantB->id))->handle();

    expect($paymentA->fresh()->status)->toBe('pending');
    expect($orderA->fresh()->payment_status)->toBe('pending');
    // O log é marcado como processado (mesmo comportamento de "payment not
    // found"), mas nada é marcado como pago.
    expect($log->fresh()->processed)->toBeTrue();

    Event::assertNotDispatched(OrderPaid::class);
});

test('webhook for txid of the correct tenant marks payment as paid', function () {
    Event::fake([OrderPaid::class]);

    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
        'payment_status' => 'pending',
    ]);

    $payment = OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'efi_pix_txid' => 'txid_of_correct_tenant',
        'idempotency_key' => Str::uuid()->toString(),
    ]);

    $log = WebhookLog::create([
        'source' => 'tenant',
        'tenant_id' => $tenant->id,
        'payload_json' => json_encode(['pix' => [['txid' => 'txid_of_correct_tenant']]]),
        'signature' => 'fake-signature',
        'is_valid' => true,
        'processed' => false,
    ]);

    (new ProcessEfiBankWebhook($log->id, 'tenant', $tenant->id))->handle();

    expect($payment->fresh()->status)->toBe('paid');
    expect($order->fresh()->payment_status)->toBe('paid');
    expect($log->fresh()->processed)->toBeTrue();

    Event::assertDispatched(OrderPaid::class);
});
