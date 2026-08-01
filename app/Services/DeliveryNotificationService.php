<?php

namespace App\Services;

use App\Models\DeliveryPerson;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeliveryNotificationService
{
    private const ICONS = [
        'order_created' => 'package',
        'delivery_accepted' => 'check-circle',
        'delivery_picked_up' => 'truck',
        'delivery_delivered' => 'check-badge',
    ];

    private const COLORS = [
        'order_created' => 'amber',
        'delivery_accepted' => 'violet',
        'delivery_picked_up' => 'blue',
        'delivery_delivered' => 'emerald',
    ];

    public function newOrderAvailable(Order $order): void
    {
        if (!$order->tenant_id) return;

        // Notify all active delivery people for this tenant
        $deliveryPeople = DeliveryPerson::where('tenant_id', $order->tenant_id)
            ->where('status', 'active')
            ->get();

        foreach ($deliveryPeople as $dp) {
            $this->create($dp, 'order_created', [
                'order_id' => $order->id,
                'message' => "Novo pedido #{$order->id} disponível!",
                'customer' => $order->customer_name,
                'total' => (float) $order->total,
                'address' => $order->address_json['address'] ?? '',
            ]);
        }
    }

    public function orderAccepted(Order $order, DeliveryPerson $delivery): void
    {
        // Notify admin/waiters of this tenant
        $this->notifyStaff($order->tenant_id, 'delivery_accepted', [
            'order_id' => $order->id,
            'message' => "{$delivery->name} aceitou o pedido #{$order->id}",
            'delivery_name' => $delivery->name,
            'delivery_phone' => $delivery->phone,
        ]);

        // Notify the customer
        $this->notifyCustomer($order, 'delivery_accepted', "Pedido #{$order->id} aceito por {$delivery->name}! Em breve saira para entrega.");
    }

    public function orderPickedUp(Order $order, DeliveryPerson $delivery): void
    {
        // Notify admin/waiters
        $this->notifyStaff($order->tenant_id, 'delivery_picked_up', [
            'order_id' => $order->id,
            'message' => "{$delivery->name} saiu para entrega do pedido #{$order->id}",
            'delivery_name' => $delivery->name,
        ]);

        // Notify the customer
        $this->notifyCustomer($order, 'delivery_picked_up', "Seu pedido #{$order->id} saiu para entrega! Fique atento.");
    }

    public function orderDelivered(Order $order, DeliveryPerson $delivery): void
    {
        // Notify admin/waiters
        $this->notifyStaff($order->tenant_id, 'delivery_delivered', [
            'order_id' => $order->id,
            'message' => "Pedido #{$order->id} entregue por {$delivery->name}",
            'delivery_name' => $delivery->name,
            'total' => (float) $order->total,
        ]);

        // Notify the delivery person themselves (for their own records)
        $this->create($delivery, 'delivery_delivered', [
            'order_id' => $order->id,
            'message' => "Pedido #{$order->id} entregue! +R\$ " . number_format((float)($order->delivery_cost ?? 0), 2, ',', '.'),
            'total' => (float) $order->total,
            'delivery_cost' => (float) ($order->delivery_cost ?? 0),
        ]);

        // Notify the customer
        $this->notifyCustomer($order, 'delivery_delivered', "Pedido #{$order->id} entregue com sucesso! Obrigado por comprar conosco.");
    }

    public function markAsRead(int $notificationId, string $notifiableType, int $notifiableId): void
    {
        Notification::where('id', $notificationId)
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->first()?->markAsRead();
    }

    public function getUnreadCount(string $notifiableType, int $notifiableId): int
    {
        return Notification::where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->unread()
            ->count();
    }

    public function getRecent(string $notifiableType, int $notifiableId, int $limit = 20): array
    {
        return Notification::where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function create(mixed $notifiable, string $type, array $data): void
    {
        try {
            Notification::create([
                'tenant_id' => $notifiable->tenant_id,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'type' => $type,
                'data' => $data,
                'icon' => self::ICONS[$type] ?? 'bell',
                'color' => self::COLORS[$type] ?? 'neutral',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar notificação: ' . $e->getMessage());
        }
    }

    private function notifyCustomer(Order $order, string $type, string $message): void
    {
        if (!$order->user_id) return;

        $user = User::find($order->user_id);
        if (!$user) return;

        $this->create($user, $type, [
            'order_id' => $order->id,
            'message' => $message,
        ]);
    }

    private function notifyStaff(int $tenantId, string $type, array $data): void
    {
        $staff = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['admin', 'atendente'])
            ->get();

        foreach ($staff as $user) {
            $this->create($user, $type, $data);
        }
    }
}
