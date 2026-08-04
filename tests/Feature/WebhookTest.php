<?php

declare(strict_types=1);

use App\Events\OrderPaid;
use App\Jobs\ProcessEfiBankWebhook;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\TenantEfiCredentials;
use App\Models\WebhookLog;
use App\Services\EfiBank\WebhookValidatorService;
use App\Services\EncryptedCredentialService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function createTenantEfiCredentials(Tenant $tenant, string $webhookSecret): TenantEfiCredentials
{
    $encryptor = app(EncryptedCredentialService::class);

    return TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $encryptor->encrypt('Client_Id_test'),
        'client_secret_encrypted' => $encryptor->encrypt('Client_Secret_test'),
        'pix_key_encrypted' => $encryptor->encrypt('pix@test.com'),
        'account_type' => 'sandbox',
        'webhook_secret_encrypted' => $encryptor->encrypt($webhookSecret),
        'is_active' => true,
    ]);
}

function signWebhookPayload(string $payload, string $secret): string
{
    return base64_encode(hash_hmac('sha256', $payload, $secret, true));
}

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

    createTenantEfiCredentials($tenant, 'tenant_secret_abc');

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
        'idempotency_key' => Str::uuid()->toString(),
    ]);

    $payload = json_encode([
        'pix' => [['txid' => 'test_txid_webhook']],
    ]);

    // NOTE: this test was updated along with the P1 fix. Before the fix,
    // tenant webhooks were validated against the global secret
    // (config('efibank.webhook_secret')), which was the vulnerability being
    // fixed. Now each tenant must be validated against its own webhook secret.
    $signature = signWebhookPayload($payload, 'tenant_secret_abc');

    $response = $this->postJson("/webhook/efi/tenant/{$tenant->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => $signature,
    ]);

    $response->assertStatus(200);

    Queue::assertPushed(ProcessEfiBankWebhook::class);
});

test('tenant webhook signed with another tenant secret is rejected with 401', function () {
    Queue::fake();

    $tenantA = createTenant();
    $tenantB = createTenant();

    createTenantEfiCredentials($tenantA, 'secret_of_tenant_A');
    createTenantEfiCredentials($tenantB, 'secret_of_tenant_B');

    $payload = json_encode(['pix' => [['txid' => 'txid_test_1']]]);

    // Payload signed with A's secret must NOT validate on B's endpoint...
    $response = $this->postJson("/webhook/efi/tenant/{$tenantB->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => signWebhookPayload($payload, 'secret_of_tenant_A'),
    ]);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'Invalid signature']);

    // ...nor B's secret on A's endpoint.
    $response = $this->postJson("/webhook/efi/tenant/{$tenantA->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => signWebhookPayload($payload, 'secret_of_tenant_B'),
    ]);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'Invalid signature']);

    Queue::assertNotPushed(ProcessEfiBankWebhook::class);
});

test('tenant webhook signed with its own secret is accepted', function () {
    Queue::fake();

    $tenant = createTenant();
    createTenantEfiCredentials($tenant, 'secret_of_tenant');

    $payload = json_encode(['pix' => [['txid' => 'txid_test_2']]]);

    $response = $this->postJson("/webhook/efi/tenant/{$tenant->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => signWebhookPayload($payload, 'secret_of_tenant'),
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);

    Queue::assertPushed(ProcessEfiBankWebhook::class);
});

test('tenant webhook without configured webhook secret is rejected with 401', function () {
    Queue::fake();

    $tenant = createTenant();

    $payload = json_encode(['pix' => [['txid' => 'txid_test_3']]]);

    // Even a signature produced with the global secret must not be accepted
    // when the tenant has no webhook secret configured.
    config(['efibank.webhook_secret' => 'global_secret']);

    $response = $this->postJson("/webhook/efi/tenant/{$tenant->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => signWebhookPayload($payload, 'global_secret'),
    ]);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'Invalid signature']);

    $log = WebhookLog::where('tenant_id', $tenant->id)->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->error_message)->toBe('Tenant webhook secret not configured');

    Queue::assertNotPushed(ProcessEfiBankWebhook::class);
});

test('tenant webhook secret does not validate on the saas endpoint', function () {
    Queue::fake();

    $tenant = createTenant();
    createTenantEfiCredentials($tenant, 'tenant_only_secret');

    config(['efibank.webhook_secret' => 'global_secret']);

    $payload = json_encode(['event' => 'test', 'charge_id' => 'ch_x']);

    $response = $this->postJson('/webhook/efi/saas', json_decode($payload, true), [
        'x-efi-hmac-sha256' => signWebhookPayload($payload, 'tenant_only_secret'),
    ]);

    $response->assertStatus(401);

    Queue::assertNotPushed(ProcessEfiBankWebhook::class);
});

test('order paid event broadcasts to tenant channel', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 50.00,
    ]);

    $event = new OrderPaid($order);

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
        'idempotency_key' => Str::uuid()->toString(),
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

test('pending renewal does not block active paid tenant', function () {
    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['status' => 'active', 'plan' => 'paid']);
    $user = createTenantAdmin($tenant);

    SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->getJson('/api/financial/summary')
        ->assertStatus(200);
});

test('saas webhook from unknown IP is rejected when IP verification is enabled', function () {
    Queue::fake();

    config(['efibank.verify_webhook_ip' => true]);
    config(['efibank.webhook_secret' => 'test_webhook_secret']);

    $payload = json_encode(['event' => 'payment_confirmed', 'charge_id' => 'ch_ip_test']);
    $signature = base64_encode(hash_hmac('sha256', $payload, 'test_webhook_secret', true));

    $response = $this->postJson('/webhook/efi/saas', json_decode($payload, true), [
        'x-efi-hmac-sha256' => $signature,
    ]);

    $response->assertStatus(401);
    expect(WebhookLog::latest()->first()->error_message)->toBe('Invalid webhook IP');
    Queue::assertNotPushed(ProcessEfiBankWebhook::class);
});

test('webhook validator accepts known efi IPs and rejects unknown ones', function () {
    $validator = app(WebhookValidatorService::class);

    config(['efibank.sandbox' => true]);
    expect($validator->validateIp('177.71.168.182'))->toBeTrue();
    expect($validator->validateIp('54.94.56.243'))->toBeTrue();

    config(['efibank.sandbox' => false]);
    expect($validator->validateIp('54.94.56.243'))->toBeTrue();
    expect($validator->validateIp('54.94.43.18'))->toBeTrue();
    expect($validator->validateIp('54.232.206.88'))->toBeTrue();
    expect($validator->validateIp('203.0.113.10'))->toBeFalse();
});

test('paid webhook with mismatched amount does not activate subscription', function () {
    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['status' => 'trial', 'plan' => 'free']);

    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'efi_charge_id' => 'txid_bad_amount',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    $log = WebhookLog::create([
        'source' => 'saas',
        'payload_json' => json_encode([
            'pix' => [['txid' => 'txid_bad_amount', 'valor' => '10.00']],
        ]),
        'signature' => 'test',
        'is_valid' => true,
        'processed' => false,
    ]);

    (new ProcessEfiBankWebhook($log->id, 'saas'))->handle();

    expect($subscription->fresh()->status)->toBe('pending');
    expect($tenant->fresh()->status)->toBe('trial');
    expect($log->fresh()->processed)->toBeFalse();
    expect($log->fresh()->error_message)->toContain('não corresponde');
});

test('paid webhook activates trial tenant', function () {
    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['status' => 'trial', 'plan' => 'free', 'max_tables' => null]);

    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'efi_charge_id' => 'txid_activate_trial',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    $log = WebhookLog::create([
        'source' => 'saas',
        'payload_json' => json_encode([
            'pix' => [['txid' => 'txid_activate_trial', 'valor' => '97.90']],
        ]),
        'signature' => 'test',
        'is_valid' => true,
        'processed' => false,
    ]);

    (new ProcessEfiBankWebhook($log->id, 'saas'))->handle();

    $tenant->refresh();
    expect($tenant->status)->toBe('active');
    expect($tenant->plan)->toBe('paid');
    expect($tenant->maxTablesAllowed())->toBe(50);
    expect($subscription->fresh()->status)->toBe('active');
    expect($log->fresh()->processed)->toBeTrue();
});
