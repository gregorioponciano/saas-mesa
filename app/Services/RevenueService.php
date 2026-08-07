<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Faturamento baseado em pagamentos efetivamente recebidos, nao em total de
 * pedidos. Assim:
 *
 * - pedido nao pago cancelado => nunca entrou no faturamento;
 * - conta fechada paga => conta no faturamento (valor "pago") mesmo apos
 *   reabertura;
 * - cancelamento de pedido pago => o pagamento e marcado como reembolsado e
 *   o valor sai do faturamento (so desconta quando ressarcido ao cliente).
 *
 * Usado pelos dois paineis (admin e atendente) com a mesma semantica de
 * periodo, garantindo valores identicos.
 */
class RevenueService
{
    public const PERIOD_TODAY = 'today';

    public const PERIOD_WEEK = 'week';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_ALL = 'all';

    public function revenue(?int $tenantId, string $period = self::PERIOD_TODAY): object
    {
        if (! $tenantId) {
            return (object) [
                'total_revenue' => 0.0,
                'delivery_revenue' => 0.0,
                'table_revenue' => 0.0,
                'pickup_revenue' => 0.0,
                'orders_today' => 0,
                'delivery_orders_today' => 0,
                'table_orders_today' => 0,
                'pickup_orders_today' => 0,
            ];
        }

        $manual = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.tenant_id', $tenantId)
            ->where('payments.status', 'paid')
            ->when($period !== self::PERIOD_ALL, fn ($q) => $this->applyPeriod($q, 'payments.paid_at', 'payments.created_at', $period))
            ->selectRaw("
                COALESCE(SUM(payments.amount), 0) as total,
                COALESCE(SUM(CASE WHEN orders.type = 'entrega' THEN payments.amount ELSE 0 END), 0) as delivery,
                COALESCE(SUM(CASE WHEN orders.type = 'mesa' THEN payments.amount ELSE 0 END), 0) as mesa,
                COALESCE(SUM(CASE WHEN orders.type = 'retirada' THEN payments.amount ELSE 0 END), 0) as retirada
            ")
            ->first();

        $efi = DB::table('order_payments')
            ->join('orders', 'orders.id', '=', 'order_payments.order_id')
            ->where('order_payments.tenant_id', $tenantId)
            ->where('order_payments.status', 'paid')
            ->when($period !== self::PERIOD_ALL, fn ($q) => $this->applyPeriod($q, 'order_payments.paid_at', 'order_payments.created_at', $period))
            ->selectRaw("
                COALESCE(SUM(order_payments.amount_cents), 0) / 100 as total,
                COALESCE(SUM(CASE WHEN orders.type = 'entrega' THEN order_payments.amount_cents ELSE 0 END), 0) / 100 as delivery,
                COALESCE(SUM(CASE WHEN orders.type = 'mesa' THEN order_payments.amount_cents ELSE 0 END), 0) / 100 as mesa,
                COALESCE(SUM(CASE WHEN orders.type = 'retirada' THEN order_payments.amount_cents ELSE 0 END), 0) / 100 as retirada
            ")
            ->first();

        $total = round((float) $manual->total + (float) $efi->total, 2);

        $counts = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->when($period !== self::PERIOD_ALL, fn ($q) => $this->applyPeriod($q, 'created_at', 'created_at', $period))
            ->selectRaw("
                COUNT(*) as orders,
                COALESCE(SUM(CASE WHEN type = 'entrega' THEN 1 ELSE 0 END), 0) as delivery_orders,
                COALESCE(SUM(CASE WHEN type = 'mesa' THEN 1 ELSE 0 END), 0) as mesa_orders,
                COALESCE(SUM(CASE WHEN type = 'retirada' THEN 1 ELSE 0 END), 0) as retirada_orders
            ")
            ->first();

        return (object) [
            'total_revenue' => $total,
            'delivery_revenue' => round((float) $manual->delivery + (float) $efi->delivery, 2),
            'table_revenue' => round((float) $manual->mesa + (float) $efi->mesa, 2),
            'pickup_revenue' => round((float) $manual->retirada + (float) $efi->retirada, 2),
            'orders_today' => (int) $counts->orders,
            'delivery_orders_today' => (int) $counts->delivery_orders,
            'table_orders_today' => (int) $counts->mesa_orders,
            'pickup_orders_today' => (int) $counts->retirada_orders,
        ];
    }

    /**
     * Serie diaria para o grafico de receita dos ultimos $days dias.
     * Retorna array chaveado por data (Y-m-d) com 'total' em reais.
     */
    public function revenueChart(?int $tenantId, int $days): array
    {
        if (! $tenantId) {
            return collect(range(0, $days - 1))
                ->mapWithKeys(fn ($i) => [now()->subDays($days - 1 - $i)->format('Y-m-d') => 0.0])
                ->all();
        }

        $startDate = now()->subDays($days - 1)->startOfDay();

        $manual = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.tenant_id', $tenantId)
            ->where('payments.status', 'paid')
            ->whereRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) >= ?', [$startDate->toDateString()])
            ->selectRaw('DATE(COALESCE(payments.paid_at, payments.created_at)) as date, SUM(payments.amount) as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $efi = DB::table('order_payments')
            ->join('orders', 'orders.id', '=', 'order_payments.order_id')
            ->where('order_payments.tenant_id', $tenantId)
            ->where('order_payments.status', 'paid')
            ->whereRaw('DATE(COALESCE(order_payments.paid_at, order_payments.created_at)) >= ?', [$startDate->toDateString()])
            ->selectRaw('DATE(COALESCE(order_payments.paid_at, order_payments.created_at)) as date, SUM(order_payments.amount_cents) / 100 as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $series = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $series[$date] = round(
                (float) ($manual[$date]->total ?? 0) + (float) ($efi[$date]->total ?? 0),
                2
            );
        }

        return $series;
    }

    private function applyPeriod($query, string $paidColumn, string $createdColumn, string $period): void
    {
        $expr = 'COALESCE('.$paidColumn.', '.$createdColumn.')';

        match ($period) {
            self::PERIOD_TODAY => $query->whereRaw('DATE('.$expr.') = ?', [now()->toDateString()]),
            self::PERIOD_WEEK => $query->whereRaw('DATE('.$expr.') >= ?', [now()->startOfWeek()->toDateString()]),
            self::PERIOD_MONTH => $query->whereRaw('DATE('.$expr.') >= ?', [now()->startOfMonth()->toDateString()]),
            default => null,
        };
    }
}
