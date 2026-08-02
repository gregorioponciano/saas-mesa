<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\Order;
use App\Models\Table;
use App\Services\DeliveryNotificationService;

test('P7: pedido de mesa gera notificacao para staff do tenant certo', function () {
    $tenant = createTenant();
    $otherTenant = createTenant();

    $admin = createTenantAdmin($tenant, ['is_staff' => false]);
    $waiter = createTenantAdmin($tenant, ['role' => 'atendente', 'is_staff' => false]);
    $customer = createTenantAdmin($tenant, ['role' => 'cliente', 'is_staff' => false]);
    $otherAdmin = createTenantAdmin($otherTenant, ['is_staff' => false]);

    $table = Table::factory()->create(['tenant_id' => $tenant->id, 'number' => '3', 'status' => 'occupied']);

    $order = Order::create([
        'tenant_id' => $tenant->id,
        'table_id' => $table->id,
        'customer_name' => 'Cliente Mesa 3',
        'total' => 42.00,
        'payment_method' => 'pix',
        'status' => 'novo',
        'type' => 'mesa',
    ]);

    app(DeliveryNotificationService::class)->newMesaOrder($order);

    expect(Notification::where('notifiable_type', 'App\Models\User')
        ->where('type', 'order_created')
        ->where('tenant_id', $tenant->id)
        ->count())->toBe(2);

    expect(Notification::where('notifiable_id', $admin->id)->where('type', 'order_created')->count())->toBe(1);
    expect(Notification::where('notifiable_id', $waiter->id)->where('type', 'order_created')->count())->toBe(1);

    expect(Notification::where('notifiable_id', $customer->id)->count())->toBe(0);

    expect(Notification::where('tenant_id', $otherTenant->id)->count())->toBe(0);
    expect(Notification::where('notifiable_id', $otherAdmin->id)->count())->toBe(0);

    $notification = Notification::where('notifiable_id', $admin->id)->first();
    expect($notification->data['order_id'])->toBe($order->id);
    expect($notification->data['message'])->toContain('pedido de mesa');
    expect($notification->data['table_number'])->toBe('3');
});
