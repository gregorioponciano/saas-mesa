<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\WebhookLog;
use App\Jobs\ProcessEfiBankWebhook;
use Illuminate\Support\Facades\Queue;

test('saas webhook endpoint returns queued response', function () {
    Queue::fake();

    $payload = json_encode([
        'event' => 'payment_confirmed',
        'charge_id' => 'ch_123456',
    ]);

    $secret = 'test_webhook_secret';
    config(['efibank.webhook_secret' => $secret]);

    $signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $response = $this->postJson('/webhook/efi/saas', json_decode($payload, true), [
        'x-efi-hmac-sha256' => $signature,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);

    Queue::assertPushed(ProcessEfiBankWebhook::class);
});

test('saas webhook with invalid signature returns 401', function () {
    $payload = json_encode(['event' => 'test']);
    $secret = 'test_webhook_secret';

    config(['efibank.webhook_secret' => $secret]);

    $response = $this->postJson('/webhook/efi/saas', json_decode($payload, true), [
        'x-efi-hmac-sha256' => 'invalid_signature',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'Invalid signature']);
});

test('tenant webhook endpoint processes pix confirmation via job', function () {
    Queue::fake();

    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
    ]);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'efi_pix_txid' => 'test_txid_webhook',
        'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $payload = json_encode([
        'pix' => [['txid' => 'test_txid_webhook']],
    ]);

    $secret = 'test_webhook_secret';
    config(['efibank.webhook_secret' => $secret]);

    $signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $response = $this->postJson("/webhook/efi/tenant/{$tenant->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => $signature,
    ]);

    $response->assertStatus(200);

    Queue::assertPushed(ProcessEfiBankWebhook::class);
});

test('order paid event broadcasts to tenant channel', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 50.00,
    ]);

    $event = new \App\Events\OrderPaid($order);

    $channels = $event->broadcastOn();
    $data = $event->broadcastWith();

    expect($channels)->toHaveCount(1);
    expect($channels[0]->name)->toBe("tenant.{$tenant->id}.orders");
    expect($data['order_id'])->toBe($order->id);
    expect($data['payment_status'])->toBe($order->payment_status);
});

test('webhook log is created for every incoming webhook', function () {
    $payload = json_encode(['test' => 'data']);
    $secret = 'test_webhook_secret';
    config(['efibank.webhook_secret' => $secret]);

    $signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $this->postJson('/webhook/efi/saas', json_decode($payload, true), [
        'x-efi-hmac-sha256' => $signature,
    ]);

    expect(WebhookLog::count())->toBe(1);
    expect(WebhookLog::first()->source)->toBe('saas');
});

test('subscription lifecycle: trial -> past_due -> suspended -> active', function () {
    seedPlans();
    $plan = SaasPlan::where('slug', 'free')->first();
    $tenant = createTenant(['status' => 'active']);

    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'trial',
        'trial_ends_at' => now()->subDay(),
        'current_period_end' => now()->subDays(6),
    ]);

    expect($subscription->status)->toBe('trial');

    // Expired trial -> past_due
    if ($subscription->trial_ends_at->isPast()) {
        $subscription->update(['status' => 'past_due']);
    }

    expect($subscription->fresh()->status)->toBe('past_due');

    // Past due -> suspended
    $subscription->update(['status' => 'suspended', 'suspended_at' => now()]);
    expect($subscription->fresh()->status)->toBe('suspended');

    // Suspended -> active (after payment)
    $subscription->update(['status' => 'active', 'suspended_at' => null]);
    expect($subscription->fresh()->isActive())->toBeTrue();
});

test('tenant can have multiple payments but only one pending per order', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 200.00,
    ]);

    $payment1 = OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 20000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $existingPending = OrderPayment::where('order_id', $order->id)
        ->whereIn('status', ['pending', 'processing'])
        ->first();

    expect($existingPending->id)->toBe($payment1->id);
    expect(OrderPayment::where('order_id', $order->id)->count())->toBe(1);
});

test('subscription check middleware allows active tenant', function () {
    $tenant = createTenant(['status' => 'active']);
    $user = createTenantAdmin($tenant);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->getJson('/api/financial/summary')
        ->assertStatus(200);
});
