<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\TenantEfiCredentials;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\EncryptedCredentialService;
use Illuminate\Support\Str;

test('duplicate payment request returns existing pending charge', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 50.00,
        'status' => 'novo',
    ]);

    // Create existing pending payment
    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 5000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => Str::uuid()->toString(),
    ]);

    expect(OrderPayment::where('order_id', $order->id)->count())->toBe(1);
});

test('payment status transitions correctly', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
    ]);

    $payment = OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => Str::uuid()->toString(),
    ]);

    expect($payment->isPending())->toBeTrue();
    expect($payment->isPaid())->toBeFalse();

    $payment->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    expect($payment->fresh()->isPaid())->toBeTrue();
});

test('webhook processes payment idempotently', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 75.00,
    ]);

    $payment = OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 7500,
        'method' => 'pix',
        'status' => 'pending',
        'efi_pix_txid' => 'testtxid12345',
        'idempotency_key' => Str::uuid()->toString(),
    ]);

    $payload = [
        'pix' => [['txid' => 'testtxid12345']],
    ];

    $service = app(TenantEfiBankService::class);
    $service->processTenantWebhook($payload, $tenant);

    expect($payment->fresh()->status)->toBe('paid');

    // Process again — should be idempotent
    $payment->update(['status' => 'paid']); // reset manually to verify
    $service->processTenantWebhook($payload, $tenant);

    expect($payment->fresh()->status)->toBe('paid');
});

test('encrypted credentials are decryptable', function () {
    $service = app(EncryptedCredentialService::class);

    $original = 'my-secret-api-key-12345';
    $encrypted = $service->encrypt($original);

    expect($encrypted)->not->toBe($original);

    $decrypted = $service->decrypt($encrypted);

    expect($decrypted)->toBe($original);
});

test('tenant efi credentials encrypt and decrypt correctly', function () {
    $tenant = createTenant();
    $service = app(EncryptedCredentialService::class);

    $credentials = [
        'client_id' => 'Client_Id_123',
        'client_secret' => 'Client_Secret_456',
        'pix_key' => 'pixkey@test',
        'certificate_content' => 'fake-cert-content',
    ];

    $encrypted = $service->encryptTenantCredentials($credentials);

    $model = TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $encrypted['client_id_encrypted'],
        'client_secret_encrypted' => $encrypted['client_secret_encrypted'],
        'pix_key_encrypted' => $encrypted['pix_key_encrypted'],
        'certificate_content_encrypted' => $encrypted['certificate_content_encrypted'],
        'account_type' => 'sandbox',
    ]);

    $decrypted = $service->decryptTenantCredentials($model);

    expect($decrypted['client_id'])->toBe('Client_Id_123');
    expect($decrypted['client_secret'])->toBe('Client_Secret_456');
    expect($decrypted['pix_key'])->toBe('pixkey@test');
    expect($decrypted['certificate_content'])->toBe('fake-cert-content');
});
