<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class NotifyOrderPaid
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        Log::info('Pedido pago - notificações disparadas', [
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'amount' => $order->total,
        ]);

        // Aqui você pode adicionar:
        // - Notificação push para o garçom
        // - Notificação por email para o cliente
        // - WebSocket broadcast (já configurado no Evento)
        // - SMS se configurado
    }
}
