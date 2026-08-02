<?php

declare(strict_types=1);

use App\Models\CustomerPoint;
use App\Models\LoyaltyConfig;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;

test('tenant A cannot access tenant B loyalty points', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $userA = createTenantAdmin($tenantA);
    $userB = createTenantAdmin($tenantB);

    CustomerPoint::create(['tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'balance' => 100]);
    CustomerPoint::create(['tenant_id' => $tenantB->id, 'user_id' => $userB->id, 'balance' => 999]);

    $this->actingAs($userA);

    expect(CustomerPoint::count())->toBe(1);
    expect(CustomerPoint::first()->balance)->toBe(100);
});

test('tenant A cannot access tenant B points transactions', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $userA = createTenantAdmin($tenantA);
    $userB = createTenantAdmin($tenantB);

    PointsTransaction::create(['tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'points' => 10, 'type' => 'earned']);
    PointsTransaction::create(['tenant_id' => $tenantB->id, 'user_id' => $userB->id, 'points' => 999, 'type' => 'earned']);

    $this->actingAs($userA);

    expect(PointsTransaction::count())->toBe(1);
    expect(PointsTransaction::first()->points)->toBe(10);
});

test('tenant A cannot access tenant B stock movements', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $userA = createTenantAdmin($tenantA);
    $productA = Product::factory()->create(['tenant_id' => $tenantA->id]);
    $productB = Product::factory()->create(['tenant_id' => $tenantB->id]);

    StockMovement::create([
        'tenant_id' => $tenantA->id, 'product_id' => $productA->id, 'quantity' => 5, 'type' => 'entry', 'stock_before' => 0, 'stock_after' => 5,
    ]);
    StockMovement::create([
        'tenant_id' => $tenantB->id, 'product_id' => $productB->id, 'quantity' => 500, 'type' => 'entry', 'stock_before' => 0, 'stock_after' => 500,
    ]);

    $this->actingAs($userA);

    expect(StockMovement::count())->toBe(1);
    expect(StockMovement::first()->quantity)->toBe(5);
});

test('tenant A cannot access tenant B loyalty config', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    LoyaltyConfig::create([
        'tenant_id' => $tenantA->id, 'points_enabled' => true, 'points_percentage' => 10,
    ]);
    LoyaltyConfig::create([
        'tenant_id' => $tenantB->id, 'points_enabled' => true, 'points_percentage' => 90,
    ]);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(LoyaltyConfig::count())->toBe(1);
    expect(LoyaltyConfig::first()->points_percentage)->toBe(10);
});

test('withoutTenant macro bypasses the scope when explicitly needed', function () {
    $tenant = createTenant(['name' => 'A']);

    LoyaltyConfig::create([
        'tenant_id' => $tenant->id, 'points_enabled' => true, 'points_percentage' => 10,
    ]);

    $this->actingAs(createTenantAdmin($tenant));

    expect(LoyaltyConfig::withoutTenant()->count())->toBe(1);
});

test('superadmin com tenant_id preenchido nao e isolado pelo scope', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    LoyaltyConfig::create([
        'tenant_id' => $tenantA->id, 'points_enabled' => true, 'points_percentage' => 10,
    ]);
    LoyaltyConfig::create([
        'tenant_id' => $tenantB->id, 'points_enabled' => false, 'points_percentage' => 20,
    ]);

    $superadmin = User::factory()->create([
        'role' => 'superadmin',
        'tenant_id' => $tenantB->id,
    ]);

    $this->actingAs($superadmin);

    expect(LoyaltyConfig::count())->toBe(2);

    $this->postJson("/api/superadmin/loyalty/{$tenantA->id}/toggle")
        ->assertOk()
        ->assertJsonPath('points_enabled', false);

    expect(LoyaltyConfig::where('tenant_id', $tenantA->id)->first()->points_enabled)->toBeFalse();
});
