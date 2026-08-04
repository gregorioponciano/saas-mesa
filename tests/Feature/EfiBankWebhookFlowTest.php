<?php

declare(strict_types=1);

use App\Events\OrderPaid;
use App\Jobs\ProcessEfiBankWebhook;
use App\Jobs\RenewTenantSubscription;
use App\Jobs\SuspendTenantAccess;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\TenantEfiCredentials;
use App\Models\WebhookLog;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\EncryptedCredentialService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function webhookFlowCredentials(Tenant $tenant, string $secret): TenantEfiCredentials
{
    $encryptor = app(EncryptedCredentialService::class);

    return TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $encryptor->encrypt('Client_Id_flow_test'),
        'client_secret_encrypted' => $encryptor->encrypt('Client_Secret_flow_test'),
        'pix_key_encrypted' => $encryptor->encrypt('pix@flow.test'),
        'account_type' => 'sandbox',
        'webhook_secret_encrypted' => $encryptor->encrypt($secret),
        'is_active' => true,
    ]);
}

function webhookFlowSignature(string $payload, string $secret): string
{
    return base64_encode(hash_hmac('sha256', $payload, $secret, true));
}

function webhookFlowPendingPayment(Order $order, Tenant $tenant, string $txid): OrderPayment
{
    return OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'efi_pix_txid' => $txid,
        'idempotency_key' => Str::uuid()->toString(),
    ]);
}

test('webhook de tenant com assinatura valida processa e atualiza status do pedido', function () {
    Event::fake([OrderPaid::class]);
    Queue::fake();

    $tenant = createTenant();
    $user = createTenantAdmin($tenant);
    webhookFlowCredentials($tenant, 'tenant_secret_flow');

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
        'payment_status' => 'pending',
    ]);
    webhookFlowPendingPayment($order, $tenant, 'txid_flow_valid');

    $payload = json_encode(['pix' => [['txid' => 'txid_flow_valid']]]);

    $response = $this->postJson("/webhook/efi/tenant/{$tenant->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => webhookFlowSignature($payload, 'tenant_secret_flow'),
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);
    Queue::assertPushed(ProcessEfiBankWebhook::class);

    $log = WebhookLog::where('source', 'tenant')->first();
    expect($log)->not->toBeNull();
    expect($log->is_valid)->toBeTrue();
    expect($log->processed)->toBeFalse();

    (new ProcessEfiBankWebhook($log->id, 'tenant', $tenant->id))->handle();

    $payment = OrderPayment::where('efi_pix_txid', 'txid_flow_valid')->first();
    $order->refresh();

    expect($payment->status)->toBe('paid');
    expect($payment->paid_at)->not->toBeNull();
    expect($order->payment_status)->toBe('paid');
    expect($order->paid_at)->not->toBeNull();
    expect($log->fresh()->processed)->toBeTrue();

    Event::assertDispatched(OrderPaid::class, fn (OrderPaid $event) => $event->order->id === $order->id);
});

test('webhook com assinatura invalida e rejeitado com 401 e registrado no WebhookLog', function () {
    Queue::fake();

    $tenant = createTenant();
    webhookFlowCredentials($tenant, 'tenant_secret_flow');

    $payload = json_encode(['pix' => [['txid' => 'txid_flow_invalid']]]);

    $response = $this->postJson("/webhook/efi/tenant/{$tenant->id}", json_decode($payload, true), [
        'x-efi-hmac-sha256' => webhookFlowSignature($payload, 'outro_secret'),
    ]);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'Invalid signature']);

    $log = WebhookLog::where('source', 'tenant')->first();
    expect($log)->not->toBeNull();
    expect($log->is_valid)->toBeFalse();
    expect($log->processed)->toBeFalse();
    expect($log->error_message)->toBe('Invalid signature');

    Queue::assertNotPushed(ProcessEfiBankWebhook::class);
});

test('job ProcessEfiBankWebhook e idempotente para reenvio do mesmo log', function () {
    Event::fake([OrderPaid::class]);

    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
        'payment_status' => 'pending',
    ]);
    $payment = webhookFlowPendingPayment($order, $tenant, 'txid_flow_idem');

    $log = WebhookLog::create([
        'source' => 'tenant',
        'tenant_id' => $tenant->id,
        'payload_json' => json_encode(['pix' => [['txid' => 'txid_flow_idem']]]),
        'signature' => 'sig',
        'is_valid' => true,
        'processed' => false,
    ]);

    $job = new ProcessEfiBankWebhook($log->id, 'tenant', $tenant->id);

    $job->handle();
    $paidAtFirst = $payment->fresh()->paid_at;
    $job->handle();

    $payment->refresh();
    $order->refresh();

    expect($payment->status)->toBe('paid');
    expect($payment->paid_at->equalTo($paidAtFirst))->toBeTrue();
    expect($order->payment_status)->toBe('paid');
    expect($log->fresh()->processed)->toBeTrue();
    expect($log->fresh()->error_message)->toBeNull();

    Event::assertDispatchedTimes(OrderPaid::class, 1);
});

test('reenvio do webhook com novo log para o mesmo txid nao duplica efeito', function () {
    Event::fake([OrderPaid::class]);

    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
        'payment_status' => 'paid',
    ]);
    $payment = webhookFlowPendingPayment($order, $tenant, 'txid_flow_resend');
    $payment->update(['status' => 'paid', 'paid_at' => now()]);

    $firstLog = WebhookLog::create([
        'source' => 'tenant',
        'tenant_id' => $tenant->id,
        'payload_json' => json_encode(['pix' => [['txid' => 'txid_flow_resend']]]),
        'signature' => 'sig',
        'is_valid' => true,
        'processed' => false,
    ]);

    (new ProcessEfiBankWebhook($firstLog->id, 'tenant', $tenant->id))->handle();

    $resendLog = WebhookLog::create([
        'source' => 'tenant',
        'tenant_id' => $tenant->id,
        'payload_json' => json_encode(['pix' => [['txid' => 'txid_flow_resend']]]),
        'signature' => 'sig',
        'is_valid' => true,
        'processed' => false,
    ]);

    (new ProcessEfiBankWebhook($resendLog->id, 'tenant', $tenant->id))->handle();

    expect($payment->fresh()->status)->toBe('paid');
    expect($order->fresh()->payment_status)->toBe('paid');
    expect(OrderPayment::where('efi_pix_txid', 'txid_flow_resend')->count())->toBe(1);

    Event::assertDispatchedTimes(OrderPaid::class, 0);
});

test('job SuspendTenantAccess suspende tenant e assinatura em caso de inadimplencia', function () {
    seedPlans();
    $plan = SaasPlan::where('slug', 'free')->first();

    $tenant = createTenant(['status' => 'active']);
    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    (new SuspendTenantAccess($tenant, 'payment_overdue'))->handle(app(SubscriptionService::class));

    expect($tenant->fresh()->status)->toBe('suspended');
    expect($subscription->fresh()->status)->toBe('suspended');
    expect($subscription->fresh()->suspended_at)->not->toBeNull();
    expect($subscription->fresh()->metadata['suspension_reason'])->toBe('payment_overdue');
});

test('job SuspendTenantAccess nao trava o sistema quando o servico falha', function () {
    $log = Log::spy();

    $tenant = createTenant(['status' => 'active']);

    $this->mock(SubscriptionService::class, function ($mock) {
        $mock->shouldReceive('suspendTenant')
            ->once()
            ->andThrow(new RuntimeException('EFI fora do ar'));
    });

    (new SuspendTenantAccess($tenant, 'payment_overdue'))->handle(app(SubscriptionService::class));

    expect($tenant->fresh()->status)->toBe('active');

    $log->shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'Failed to suspend tenant access'));
});

test('job RenewTenantSubscription cria nova cobranca e estende periodo', function () {
    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->first();

    $tenant = createTenant(['status' => 'active', 'plan' => 'paid']);
    $periodEnd = now()->addDays(10);
    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_start' => now()->subMonth(),
        'current_period_end' => $periodEnd,
        'next_billing_date' => now()->addDays(3),
    ]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('createSubscriptionCharge')
            ->once()
            ->andReturn(['efi_charge_id' => 'txid_renewal_1', 'pix_copy_paste' => '000201...']);
    });

    (new RenewTenantSubscription($subscription))->handle(app(SaasEfiBankService::class));

    $subscription->refresh();

    $expectedPeriodEnd = $periodEnd->copy()->startOfSecond()->addMonth();

    expect($subscription->status)->toBe('pending');
    expect($subscription->suspended_at)->toBeNull();
    expect($subscription->current_period_end->toDateTimeString())->toBe($expectedPeriodEnd->toDateTimeString());
    expect($subscription->next_billing_date->toDateTimeString())->toBe($expectedPeriodEnd->toDateTimeString());
});

test('job RenewTenantSubscription mantem assinatura ativa quando a cobranca falha', function () {
    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->first();

    $tenant = createTenant(['status' => 'active', 'plan' => 'paid']);
    $periodEnd = now()->addDays(10);
    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_start' => now()->subMonth(),
        'current_period_end' => $periodEnd,
        'next_billing_date' => now()->addDays(3),
    ]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('createSubscriptionCharge')
            ->once()
            ->andReturnNull();
    });

    (new RenewTenantSubscription($subscription))->handle(app(SaasEfiBankService::class));

    $subscription->refresh();

    expect($subscription->status)->toBe('active');
    expect($subscription->current_period_end->toDateTimeString())->toBe($periodEnd->copy()->startOfSecond()->toDateTimeString());
});

test('job RenewTenantSubscription nao trava o sistema quando a API EfiBank lanca excecao', function () {
    $log = Log::spy();
    seedPlans();
    $plan = SaasPlan::where('slug', 'premium')->first();

    $tenant = createTenant(['status' => 'active', 'plan' => 'paid']);
    $periodEnd = now()->addDays(10);
    $subscription = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_start' => now()->subMonth(),
        'current_period_end' => $periodEnd,
        'next_billing_date' => now()->addDays(3),
    ]);

    $this->mock(SaasEfiBankService::class, function ($mock) {
        $mock->shouldReceive('createSubscriptionCharge')
            ->once()
            ->andThrow(new RuntimeException('EFI fora do ar'));
    });

    (new RenewTenantSubscription($subscription))->handle(app(SaasEfiBankService::class));

    $subscription->refresh();

    expect($subscription->status)->toBe('active');
    expect($subscription->current_period_end->toDateTimeString())->toBe($periodEnd->copy()->startOfSecond()->toDateTimeString());

    $log->shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'Failed to renew tenant subscription'));
});
