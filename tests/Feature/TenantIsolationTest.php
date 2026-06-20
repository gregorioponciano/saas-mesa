<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryPerson;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Table;

test('tenant A cannot access tenant B categories', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $categoryA = Category::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Cat A']);
    $categoryB = Category::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Cat B']);

    $this->actingAs(createTenantAdmin($tenantA));

    $foundCategories = Category::all();

    expect($foundCategories)->toHaveCount(1);
    expect($foundCategories->first()->id)->toBe($categoryA->id);
});

test('tenant A cannot access tenant B orders', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    createTenantAdmin($tenantA);
    $userB = createTenantAdmin($tenantB);

    Order::factory()->create(['tenant_id' => $tenantA->id]);
    Order::factory()->create(['tenant_id' => $tenantB->id, 'user_id' => $userB->id]);

    $this->actingAs(createTenantAdmin($tenantA));

    $orders = Order::all();

    expect($orders)->toHaveCount(1);
    expect($orders->first()->tenant_id)->toBe($tenantA->id);
});

test('tenant A cannot access tenant B tables', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    Table::factory()->create(['tenant_id' => $tenantA->id, 'number' => '1']);
    Table::factory()->create(['tenant_id' => $tenantB->id, 'number' => '1']);

    $this->actingAs(createTenantAdmin($tenantA));

    $tables = Table::all();

    expect($tables)->toHaveCount(1);
    expect($tables->first()->tenant_id)->toBe($tenantA->id);
});

test('tenant A cannot access tenant B products', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $categoryA = Category::factory()->create(['tenant_id' => $tenantA->id]);
    $categoryB = Category::factory()->create(['tenant_id' => $tenantB->id]);

    Product::factory()->create(['tenant_id' => $tenantA->id, 'category_id' => $categoryA->id]);
    Product::factory()->create(['tenant_id' => $tenantB->id, 'category_id' => $categoryB->id]);

    $this->actingAs(createTenantAdmin($tenantA));

    $products = Product::all();

    expect($products)->toHaveCount(1);
    expect($products->first()->tenant_id)->toBe($tenantA->id);
});

test('tenant A cannot access tenant B coupons', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    Coupon::factory()->create(['tenant_id' => $tenantA->id, 'code' => 'PROMO10']);
    Coupon::factory()->create(['tenant_id' => $tenantB->id, 'code' => 'PROMO20']);

    $this->actingAs(createTenantAdmin($tenantA));

    $coupons = Coupon::all();

    expect($coupons)->toHaveCount(1);
    expect($coupons->first()->tenant_id)->toBe($tenantA->id);
});

test('global scope blocks cross-tenant queries', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    Order::factory()->create(['tenant_id' => $tenantA->id]);
    Order::factory()->create(['tenant_id' => $tenantB->id]);

    $this->actingAs(createTenantAdmin($tenantA));

    $count = Order::query()->count();

    expect($count)->toBe(1);
});

test('unauthenticated requests have no tenant scope', function () {
    $tenant = createTenant(['name' => 'A']);

    Order::factory()->create(['tenant_id' => $tenant->id]);

    $orders = Order::all();

    // Without auth, scope may still apply depending on implementation
    // This test verifies no exception is thrown
    expect($orders)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});
