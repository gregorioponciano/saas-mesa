<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DeliveryPerson;
use App\Models\Order;

class DeliveryPersonPolicy
{
    public function viewOrder(DeliveryPerson $delivery, Order $order): bool
    {
        return $order->tenant_id === $delivery->tenant_id;
    }

    public function acceptOrder(DeliveryPerson $delivery, Order $order): bool
    {
        return $order->tenant_id === $delivery->tenant_id
            && $order->type === 'entrega'
            && $order->delivery_person_id === null
            && in_array($order->status, ['novo', 'em_preparo']);
    }

    public function refuseOrder(DeliveryPerson $delivery, Order $order): bool
    {
        return $order->tenant_id === $delivery->tenant_id
            && $order->type === 'entrega'
            && $order->delivery_person_id === null
            && in_array($order->status, ['novo', 'em_preparo']);
    }

    public function updateOrder(DeliveryPerson $delivery, Order $order): bool
    {
        return $order->tenant_id === $delivery->tenant_id
            && $order->delivery_person_id === $delivery->id;
    }
}
