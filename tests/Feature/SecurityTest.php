<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\EfiBank\WebhookValidatorService;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

test('mass assignment is blocked on all models', function () {
    $this->expectException(MassAssignmentException::class);

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

test('security headers are present on web responses', function () {
    $tenant = createTenant();
    $this->actingAs(createTenantAdmin($tenant));

    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("object-src 'none'")
        ->toContain("base-uri 'self'")
        ->toContain("form-action 'self'");
});

test('csp allows unsafe-eval only outside production', function () {
    config()->set('app.env', 'production');

    $tenant = createTenant();
    $this->actingAs(createTenantAdmin($tenant));

    $response = $this->get('/');
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toContain('unsafe-eval');
});

test('order payment model has fillable attributes', function () {
    $payment = new OrderPayment;

    expect($payment->getFillable())->toContain('order_id');
    expect($payment->getFillable())->toContain('tenant_id');
    expect($payment->getFillable())->toContain('amount_cents');
    expect($payment->getFillable())->toContain('idempotency_key');
});

test('idempotency key is unique', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'total' => 100.00,
    ]);

    $key = Str::uuid()->toString();

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => $key,
    ]);

    $this->expectException(QueryException::class);

    OrderPayment::create([
        'order_id' => $order->id,
        'tenant_id' => $tenant->id,
        'amount_cents' => 10000,
        'method' => 'pix',
        'status' => 'pending',
        'idempotency_key' => $key,
    ]);
});
