<?php

declare(strict_types=1);

use App\Livewire\Admin\CouponManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\TablesPage;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

test('product policy: admin can only update/delete products of its own tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $categoryA = Category::factory()->create(['tenant_id' => $tenantA->id]);
    $categoryB = Category::factory()->create(['tenant_id' => $tenantB->id]);

    $productA = Product::factory()->create(['tenant_id' => $tenantA->id, 'category_id' => $categoryA->id]);
    $productB = Product::factory()->create(['tenant_id' => $tenantB->id, 'category_id' => $categoryB->id]);

    $adminA = createTenantAdmin($tenantA);

    $this->actingAs($adminA);

    expect($adminA->can('view', $productA))->toBeTrue();
    expect($adminA->can('update', $productA))->toBeTrue();
    expect($adminA->can('delete', $productA))->toBeTrue();
    expect($adminA->can('create', Product::class))->toBeTrue();

    expect($adminA->can('view', $productB))->toBeFalse();
    expect($adminA->can('update', $productB))->toBeFalse();
    expect($adminA->can('delete', $productB))->toBeFalse();
});

test('category policy: admin can only update/delete categories of its own tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $categoryA = Category::factory()->create(['tenant_id' => $tenantA->id]);
    $categoryB = Category::factory()->create(['tenant_id' => $tenantB->id]);

    $adminA = createTenantAdmin($tenantA);

    $this->actingAs($adminA);

    expect($adminA->can('update', $categoryA))->toBeTrue();
    expect($adminA->can('delete', $categoryA))->toBeTrue();
    expect($adminA->can('update', $categoryB))->toBeFalse();
    expect($adminA->can('delete', $categoryB))->toBeFalse();
});

test('coupon policy: admin can only update/delete coupons of its own tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $couponA = Coupon::factory()->create(['tenant_id' => $tenantA->id]);
    $couponB = Coupon::factory()->create(['tenant_id' => $tenantB->id]);

    $adminA = createTenantAdmin($tenantA);

    $this->actingAs($adminA);

    expect($adminA->can('update', $couponA))->toBeTrue();
    expect($adminA->can('delete', $couponA))->toBeTrue();
    expect($adminA->can('update', $couponB))->toBeFalse();
    expect($adminA->can('delete', $couponB))->toBeFalse();
});

test('table policy: admin can only update/delete tables of its own tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $tableA = Table::factory()->create(['tenant_id' => $tenantA->id]);
    $tableB = Table::factory()->create(['tenant_id' => $tenantB->id]);

    $adminA = createTenantAdmin($tenantA);

    $this->actingAs($adminA);

    expect($adminA->can('update', $tableA))->toBeTrue();
    expect($adminA->can('delete', $tableA))->toBeTrue();
    expect($adminA->can('update', $tableB))->toBeFalse();
    expect($adminA->can('delete', $tableB))->toBeFalse();
});

test('user policy: admin can only update/delete users of its own tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $userA = createTenantAdmin($tenantA);
    $userB = createTenantAdmin($tenantB);

    $this->actingAs($userA);

    expect($userA->can('update', $userA))->toBeTrue();
    expect($userA->can('update', $userB))->toBeFalse();
    expect($userA->can('delete', $userB))->toBeFalse();
});

test('happy path: menu manager product/category flows keep working with policies', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);

    Livewire::actingAs($admin)
        ->test(MenuManager::class)
        ->call('openCreateProduct')
        ->set('productName', 'X-Burger Policy')
        ->set('productPrice', '25.90')
        ->set('productCategoryId', $category->id)
        ->set('productStatus', 'active')
        ->call('saveProduct')
        ->assertHasNoErrors();

    expect(Product::where('tenant_id', $tenant->id)->where('name', 'X-Burger Policy')->exists())->toBeTrue();

    $product = Product::where('tenant_id', $tenant->id)->where('name', 'X-Burger Policy')->first();

    Livewire::actingAs($admin)
        ->test(MenuManager::class)
        ->call('deleteProduct', $product->id);

    expect(Product::find($product->id))->toBeNull();
});

test('happy path: coupon and table and user manager flows keep working with policies', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    Livewire::actingAs($admin)
        ->test(CouponManager::class)
        ->set('code', 'PROMO')
        ->set('discountType', 'percentage')
        ->set('discountValue', 10)
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::where('tenant_id', $tenant->id)->where('code', 'PROMO')->first();
    expect($coupon)->not->toBeNull();

    Livewire::actingAs($admin)
        ->test(CouponManager::class)
        ->call('delete', $coupon->id);

    expect(Coupon::find($coupon->id))->toBeNull();

    Livewire::actingAs($admin)
        ->test(TablesPage::class)
        ->set('formMode', 'single')
        ->set('number', '7')
        ->set('capacity', 4)
        ->call('save')
        ->assertHasNoErrors();

    $table = Table::where('tenant_id', $tenant->id)->where('number', '7')->first();
    expect($table)->not->toBeNull();

    Livewire::actingAs($admin)
        ->test(TablesPage::class)
        ->call('delete', $table->id);

    expect(Table::find($table->id))->toBeNull();

    // User: o componente UserManager usa orderByRaw(FIELD(...)) (MySQL-only),
    // que não roda em SQLite — por isso o caminho feliz do usuário é validado
    // no nível da policy/Gate.
    $sameTenantUser = createTenantAdmin($tenant, ['role' => 'cliente']);
    expect($admin->can('update', $sameTenantUser))->toBeTrue();
    expect($admin->can('delete', $sameTenantUser))->toBeTrue();
    expect($admin->can('create', User::class))->toBeTrue();
});
