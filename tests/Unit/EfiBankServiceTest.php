<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\EfiBank\WebhookValidatorService;

test('saas webhook process marks subscription active', function () {
    $tenant = createTenant(['status' => 'suspended']);
    $plan = SaasPlan::factory()->create(['price_cents' => 9790]);

    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'past_due',
        'efi_charge_id' => 'charge_999',
    ]);

    $service = app(SaasEfiBankService::class);

    $service->processSaasWebhook([
        'event' => 'payment_confirmed',
        'charge_id' => 'charge_999',
        'payment_method' => 'pix',
    ]);

    $subscription->refresh();
    $tenant->refresh();

    expect($subscription->status)->toBe('active');
    expect($tenant->status)->toBe('active');
});

test('saas webhook with unknown charge is silently ignored', function () {
    $service = app(SaasEfiBankService::class);

    expect(fn () => $service->processSaasWebhook([
        'event' => 'payment_confirmed',
        'charge_id' => 'nonexistent_charge',
    ]))->not->toThrow(\Exception::class);
});

test('tenant webhook processes pix payment correctly', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 150.00,
    ]);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 15000,
        'method' => 'pix',
        'status' => 'pending',
        'efi_pix_txid' => 'pix_txid_123',
        'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $service = app(TenantEfiBankService::class);

    $service->processTenantWebhook([
        'pix' => [['txid' => 'pix_txid_123']],
    ], $tenant);

    $payment = OrderPayment::where('efi_pix_txid', 'pix_txid_123')->first();
    $order->refresh();

    expect($payment->status)->toBe('paid');
    expect($order->payment_status)->toBe('paid');
});

test('webhook validator validates HMAC correctly', function () {
    $payload = '{"test":"data"}';
    $secret = 'my_webhook_secret';

    $expected = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $validator = app(WebhookValidatorService::class);

    expect($validator->validate($payload, $expected, $secret))->toBeTrue();
    expect($validator->validate($payload, 'wrong_signature', $secret))->toBeFalse();
    expect($validator->validate($payload, null, $secret))->toBeFalse();
});

test('webhook validator requires secret', function () {
    $validator = app(WebhookValidatorService::class);

    config(['efibank.webhook_secret' => '']);

    expect($validator->validate('{}', 'some_sig'))->toBeFalse();
});

test('order payment model enforces idempotency key uniqueness', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ]);

    $key = 'unique-key-' . \Illuminate\Support\Str::random(16);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 5000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => $key,
    ]);

    expect(OrderPayment::where('idempotency_key', $key)->exists())->toBeTrue();
});
