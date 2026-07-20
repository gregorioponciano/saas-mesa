<?php

declare(strict_types=1);

use App\Models\CustomerPoint;
use App\Models\LoyaltyConfig;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PointsTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PointsService;

beforeEach(function () {
    $this->service = app(PointsService::class);
    $this->tenant = Tenant::factory()->create(['plan' => Tenant::PLAN_PAID]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => User::ROLE_CLIENTE,
    ]);

    LoyaltyConfig::forTenant($this->tenant)->update(['points_enabled' => true]);
});

test('cliente de empresa nao-Premium nunca ve pontos', function () {
    $freeTenant = Tenant::factory()->create(['plan' => Tenant::PLAN_FREE]);

    expect($this->service->arePointsVisibleForCustomer($freeTenant))->toBeFalse();

    $config = LoyaltyConfig::forTenant($freeTenant);
    $config->update(['points_enabled' => true]);

    expect($this->service->arePointsVisibleForCustomer($freeTenant))->toBeFalse();
});

test('downgrade desativa pontos automaticamente', function () {
    $config = LoyaltyConfig::forTenant($this->tenant);
    expect($config->points_enabled)->toBeTrue();

    $this->tenant->update(['plan' => Tenant::PLAN_FREE]);

    $config->refresh();
    expect($config->points_enabled)->toBeFalse();
});

test('calculo de 1% de pontos correto com arredondamento', function () {
    LoyaltyConfig::forTenant($this->tenant)->update(['points_enabled' => true, 'points_percentage' => 1]);

    $order = Order::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'total' => 150.00,
        'delivery_cost' => 10.00,
        'status' => 'entregue',
    ]);

    $result = $this->service->grantPointsForOrder($order);

    expect($result)->toBeTrue();

    $balance = CustomerPoint::where('tenant_id', $this->tenant->id)
        ->where('user_id', $this->user->id)
        ->first();

    expect($balance)->not->toBeNull();
    expect($balance->balance)->toBe(140);
});

test('idempotencia: nao duplica pontos se pedido for reprocessado', function () {
    LoyaltyConfig::forTenant($this->tenant)->update(['points_enabled' => true, 'points_percentage' => 1]);

    $order = Order::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'total' => 100.00,
        'delivery_cost' => 0,
        'status' => 'entregue',
    ]);

    $this->service->grantPointsForOrder($order);
    $this->service->grantPointsForOrder($order);
    $this->service->grantPointsForOrder($order);

    $transactions = PointsTransaction::where('order_id', $order->id)
        ->where('type', PointsTransaction::TYPE_EARNED)
        ->count();

    expect($transactions)->toBe(1);

    $balance = CustomerPoint::getBalance($this->tenant, $this->user);
    expect($balance)->toBe(100);
});

test('nao concede pontos sem usuario logado', function () {
    $order = Order::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => null,
        'total' => 100.00,
        'status' => 'entregue',
    ]);

    $result = $this->service->grantPointsForOrder($order);

    expect($result)->toBeFalse();
});

test('nao concede pontos se tenant tiver pontos desativados', function () {
    LoyaltyConfig::forTenant($this->tenant)->update(['points_enabled' => false]);

    $order = Order::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'total' => 100.00,
        'status' => 'entregue',
    ]);

    $result = $this->service->grantPointsForOrder($order);

    expect($result)->toBeFalse();
});

test('nao concede pontos se tenant nao for Premium mesmo com config ativa', function () {
    $freeTenant = Tenant::factory()->create(['plan' => Tenant::PLAN_FREE]);
    LoyaltyConfig::forTenant($freeTenant)->update(['points_enabled' => true]);

    $order = Order::factory()->create([
        'tenant_id' => $freeTenant->id,
        'user_id' => $this->user->id,
        'total' => 100.00,
        'status' => 'entregue',
    ]);

    $result = $this->service->grantPointsForOrder($order);

    expect($result)->toBeFalse();
});

test('estorno de pontos no cancelamento', function () {
    LoyaltyConfig::forTenant($this->tenant)->update(['points_enabled' => true, 'points_percentage' => 1]);

    $order = Order::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'total' => 200.00,
        'delivery_cost' => 0,
        'status' => 'fechado',
    ]);

    $this->service->grantPointsForOrder($order);

    $balanceBefore = CustomerPoint::getBalance($this->tenant, $this->user);
    expect($balanceBefore)->toBe(200);

    $this->service->reversePointsForOrder($order);

    $balanceAfter = CustomerPoint::getBalance($this->tenant, $this->user);
    expect($balanceAfter)->toBe(0);

    $reversalCount = PointsTransaction::where('order_id', $order->id)
        ->where('type', PointsTransaction::TYPE_REVERSED)
        ->count();
    expect($reversalCount)->toBe(1);
});

test('estorno de pontos e idempotente', function () {
    LoyaltyConfig::forTenant($this->tenant)->update(['points_enabled' => true, 'points_percentage' => 1]);

    $order = Order::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'total' => 100.00,
        'delivery_cost' => 0,
        'status' => 'fechado',
    ]);

    $this->service->grantPointsForOrder($order);
    $this->service->reversePointsForOrder($order);
    $this->service->reversePointsForOrder($order);

    $reversals = PointsTransaction::where('order_id', $order->id)
        ->where('type', PointsTransaction::TYPE_REVERSED)
        ->count();

    expect($reversals)->toBe(1);
});
