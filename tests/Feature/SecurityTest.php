<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\OrderPayment;
use App\Services\EfiBank\WebhookValidatorService;

test('mass assignment is blocked on all models', function () {
    $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);

    Category::create(['not_a_column' => 'test']);
});

test('webhook endpoint rejects invalid HMAC', function () {
    $payload = json_encode(['event' => 'test', 'charge_id' => '123']);
    $secret = 'test_secret';

    $validator = app(WebhookValidatorService::class);

    $valid = $validator->validate($payload, 'invalid_signature', $secret);

    expect($valid)->toBeFalse();
});

test('webhook endpoint accepts valid HMAC', function () {
    $payload = json_encode(['event' => 'test', 'charge_id' => '123']);
    $secret = 'test_secret';

    $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $validator = app(WebhookValidatorService::class);

    $valid = $validator->validate($payload, $expectedSignature, $secret);

    expect($valid)->toBeTrue();
});

test('SQL injection attempt is blocked by Eloquent', function () {
    $tenant = createTenant();

    $category = Category::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => "'; DROP TABLE categories; --",
    ]);

    expect($category->name)->toBe("'; DROP TABLE categories; --");
    expect(Category::count())->toBe(1);
});

test('order payment model has fillable attributes', function () {
    $payment = new OrderPayment();

    expect($payment->getFillable())->toContain('order_id');
    expect($payment->getFillable())->toContain('tenant_id');
    expect($payment->getFillable())->toContain('amount_cents');
    expect($payment->getFillable())->toContain('idempotency_key');
});

test('idempotency key is unique', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);
    $order = \App\Models\Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
    ]);

    $key = \Illuminate\Support\Str::uuid()->toString();

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => $key,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => $key,
    ]);
});
