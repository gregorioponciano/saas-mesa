<?php

declare(strict_types=1);

use App\Http\Controllers\SubscriptionController;
use App\Livewire\Public\Menu;
use App\Livewire\Waiter\WaiterDashboard;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('scopeStaff inclui atendentes e nao vaza admins de outro tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    createTenantAdmin($tenantA, ['role' => 'atendente']);
    createTenantAdmin($tenantA, ['role' => 'admin']);
    createTenantAdmin($tenantB, ['role' => 'admin']);

    $staff = User::query()->staff()->where('tenant_id', $tenantA->id)->pluck('tenant_id')->unique();

    expect($staff)->toContain($tenantA->id)
        ->not->toContain($tenantB->id);
});

it('password do entregador eh sempre hashed', function () {
    $tenant = createTenant();
    $delivery = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Entregador',
        'phone' => '(11) 91111-1111',
        'status' => 'active',
        'password' => 'segredo123',
    ]);

    expect(Hash::isHashed($delivery->fresh()->password))->toBeTrue();
    expect(Hash::check('segredo123', $delivery->fresh()->password))->toBeTrue();
});

it('cancel da assinatura cancela a mais recente e nao a mais antiga', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $freePlan = SaasPlan::create([
        'name' => 'Gratuito',
        'slug' => 'free',
        'price_cents' => 0,
        'is_active' => true,
        'features_json' => [],
    ]);

    $old = SaasSubscription::forceCreate([
        'tenant_id' => $tenant->id,
        'plan_id' => $freePlan->id,
        'status' => 'cancelled',
        'current_period_start' => now()->subMonths(3),
        'current_period_end' => now()->subMonths(2),
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $recent = SaasSubscription::forceCreate([
        'tenant_id' => $tenant->id,
        'plan_id' => $freePlan->id,
        'status' => 'active',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant->update(['subscription_id' => $recent->id, 'plan' => 'free']);

    $this->actingAs($admin);
    $controller = app(SubscriptionController::class);
    $controller->cancel();

    expect($old->fresh()->status)->toBe('cancelled');
    expect($recent->fresh()->status)->toBe('cancelled');
});

it('atendente nao consegue remover entregador (guar admin)', function () {
    $tenant = createTenant(['plan' => 'paid']);
    $staff = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'type' => 'entrega',
        'status' => 'saiu_entrega',
    ]);

    $this->actingAs($staff);

    try {
        (new WaiterDashboard)->removeDeliveryPerson($order->id);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});

it('guest pode liberar mesa ao sair (anti ocupacao indefinida)', function () {
    $tenant = createTenant();
    $table = Table::factory()->create(['tenant_id' => $tenant->id, 'status' => 'occupied']);

    $menu = new Menu;
    $menu->tenant = $tenant;
    $menu->selectedTableId = $table->id;
    $menu->selectedTableNumber = $table->number;
    $menu->leaveTable();

    expect($table->fresh()->status)->toBe('free');
});
