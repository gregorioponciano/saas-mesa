<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Tenant;

function createP6Order(Tenant $tenant): Order
{
    return Order::create([
        'tenant_id' => $tenant->id,
        'customer_name' => 'Cliente Teste',
        'total' => 25.00,
        'payment_method' => 'pix',
        'status' => 'novo',
        'type' => 'mesa',
    ]);
}

test('P6: order policy permite ver pedido do proprio tenant e nega de outro', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $orderA = createP6Order($tenantA);
    $orderB = createP6Order($tenantB);

    $adminA = createTenantAdmin($tenantA);
    $adminB = createTenantAdmin($tenantB);
    $super = createSuperAdmin();

    $this->actingAs($adminA);

    expect($adminA->can('view', $orderA))->toBeTrue();
    expect($adminA->can('view', $orderB))->toBeFalse();

    $this->actingAs($adminB);
    expect($adminB->can('view', $orderB))->toBeTrue();

    $this->actingAs($super);
    expect($super->can('view', $orderA))->toBeTrue();
    expect($super->can('view', $orderB))->toBeTrue();
});

test('P6: api de pagamento nega pedido de outro tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $orderA = createP6Order($tenantA);
    $adminB = createTenantAdmin($tenantB);

    // 404: o route binding do Order aplica o TenantScope global,
    // entao o pedido do tenant A nao existe para o admin do tenant B.
    $this->actingAs($adminB)
        ->getJson("/api/orders/{$orderA->id}/payment/status")
        ->assertStatus(404);
});

test('P6: api de pagamento aceita pedido do proprio tenant', function () {
    $tenantA = createTenant();
    $orderA = createP6Order($tenantA);
    $adminA = createTenantAdmin($tenantA);

    $this->actingAs($adminA)
        ->getJson("/api/orders/{$orderA->id}/payment/status")
        ->assertStatus(404)
        ->assertJson(['error' => 'no_payment']);
});
