<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\PointsService;
use Illuminate\Support\Facades\Log;

class NotifyOrderPaid
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        Log::info('Pedido pago - processando', [
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'amount' => $order->total,
        ]);

        try {
            app(PointsService::class)->grantPointsForOrder($order);
        } catch (\Throwable $e) {
            Log::error('Erro ao conceder pontos por pagamento', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
