<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ciclo de vida dos pedidos com regras de protecao ao consumidor (CDC):
 *
 * - Cliente: cancela apenas o proprio pedido, sem pagamento efetuado, ainda
 *   em "novo"/"em_preparo" e dentro da janela de arrependimento.
 * - Atendente: cancela pedidos ativos sem pagamento efetuado.
 * - Administrador: cancela qualquer pedido (inclusive contas fechadas),
 *   reabre contas e estorna pagamentos (ressarcimento).
 *
 * Pagamento efetuado => somente administrador pode alterar historico/reabrir.
 * Estoque devolvido somente se o produto NAO foi entregue; o ledger de
 * StockMovement preserva o estoque verdadeiro (saida na venda, retorno no
 * cancelamento) e o valor so deixa de contar no faturamento quando o
 * pagamento e marcado como reembolsado.
 */
class OrderLifecycleService
{
    public const ACTOR_CLIENT = 'cliente';

    public const ACTOR_ATTENDANT = 'atendente';

    public const ACTOR_ADMIN = 'admin';

    public function __construct(
        private readonly StockService $stockService,
        private readonly PointsService $pointsService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Retorna mensagem de erro se o ator nao pode cancelar o pedido, ou ''.
     */
    public function assertCanCancel(Order $order, User $actor, string $actorRole): string
    {
        if ($order->isCancelled()) {
            return 'Pedido ja foi cancelado.';
        }

        if ($actorRole === self::ACTOR_ADMIN) {
            return '';
        }

        if ($order->isBillClosed() || $order->status === 'fechado') {
            return 'Conta fechada: apenas administradores podem alterar o historico.';
        }

        if ($order->isFinished()) {
            return 'Pedido finalizado: apenas administradores podem alterar o historico.';
        }

        if ($order->hasPayment()) {
            return 'Pagamento ja efetuado: apenas administradores podem cancelar e ressarcir.';
        }

        if ($actorRole === self::ACTOR_CLIENT) {
            if ($order->user_id !== $actor->id) {
                return 'Voce nao pode cancelar o pedido de outro cliente.';
            }

            if (! in_array($order->status, ['novo', 'em_preparo'])) {
                return 'O pedido ja esta em andamento e nao pode mais ser cancelado.';
            }

            if (! $order->withinClientCancellationWindow()) {
                return 'Janela de cancelamento do cliente expirada ('.Order::CLIENT_CANCELLATION_WINDOW_MINUTES.' minutos). Solicite o cancelamento ao restaurante.';
            }
        }

        return '';
    }

    /**
     * Cancela um pedido aplicando todas as regras de negocio:
     * estorno de pagamentos, reversao de pontos, devolucao de estoque
     * (somente se nada foi entregue) e liberacao de mesa.
     */
    public function cancelOrder(Order $order, User $actor, string $actorRole, ?string $reason = null): array
    {
        $error = $this->assertCanCancel($order, $actor, $actorRole);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $refundedAmount = 0.0;
        $wasDelivered = $order->isDelivered();

        DB::transaction(function () use ($order, $actor, $reason, &$refundedAmount, &$wasDelivered) {
            if ($order->hasPayment() || $order->orderPayments()->whereIn('status', ['paid', 'pending', 'processing'])->exists()) {
                $refundedAmount = $this->refundPayments($order, $actor, $reason ?: 'Cancelamento de pedido');
            }

            $order->update([
                'status' => 'cancelado',
                'bill_closed_at' => null,
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason ? substr($reason, 0, 255) : null,
            ]);

            $this->handlePointsOnCancellation($order);

            if (! $wasDelivered) {
                try {
                    $this->stockService->returnOrderStock($order->fresh(), $actor->id);
                } catch (\Throwable $e) {
                    Log::error('Erro ao devolver estoque no cancelamento', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($order->table_id) {
                if (! Table::hasOtherActiveOrders($order->table_id, $order->id)) {
                    $order->table()->update(['status' => 'free']);
                }
            }
        });

        $this->auditService->log(
            'order.cancelled',
            "Pedido #{$order->id} cancelado por {$actor->roleLabel()}".($reason ? " - Motivo: {$reason}" : ''),
            [
                'order_id' => $order->id,
                'actor_id' => $actor->id,
                'actor_role' => $actorRole,
                'reason' => $reason,
                'refunded_amount' => round($refundedAmount, 2),
                'stock_returned' => ! $wasDelivered,
            ],
            $order->tenant,
            'order',
            (string) $order->id
        );

        return [
            'success' => true,
            'order_id' => $order->id,
            'refunded_amount' => round($refundedAmount, 2),
            'stock_returned' => ! $wasDelivered,
        ];
    }

    /**
     * Reabre conta fechada. Apenas administrador. Pagamentos permanecem
     * "pago" (o valor continua contando no faturamento ate o cancelamento
     * e ressarcimento); pontos ganhos no fechamento sao estornados e os
     * pontos usados devolvidos, pois a compra deixou de ser final.
     */
    public function reopenAccount(Order $order, User $admin, ?string $reason = null): array
    {
        if (! $admin->isAdmin()) {
            return ['success' => false, 'error' => 'Apenas administradores podem reabrir contas.'];
        }

        if ($order->status !== 'fechado') {
            return ['success' => false, 'error' => 'Apenas contas fechadas podem ser reabertas.'];
        }

        DB::transaction(function () use ($order) {
            try {
                $this->pointsService->reversePointsForOrder($order);
            } catch (\Throwable $e) {
                Log::error('Erro ao estornar pontos na reabertura', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }

            try {
                $this->pointsService->refundSpentPointsForOrder($order);
            } catch (\Throwable $e) {
                Log::error('Erro ao devolver pontos gastos na reabertura', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }

            $order->update([
                'status' => 'entregue',
                'bill_closed_at' => null,
            ]);

            if ($order->table_id) {
                $order->table()->update(['status' => 'occupied']);
            }
        });

        $this->auditService->log(
            'order.reopened',
            "Conta #{$order->id} reaberta por {$admin->roleLabel()}".($reason ? " - Motivo: {$reason}" : ''),
            ['order_id' => $order->id, 'admin_id' => $admin->id, 'reason' => $reason],
            $order->tenant,
            'order',
            (string) $order->id
        );

        return ['success' => true, 'order_id' => $order->id];
    }

    /**
     * Cancela um item do pedido preservando o historico (soft delete).
     * Estoque devolvido apenas se o pedido nao foi entregue. Pagamento
     * efetuado => apenas administrador pode alterar o historico.
     */
    public function cancelItem(OrderItem $item, User $actor, string $actorRole, ?string $reason = null): array
    {
        $order = $item->order;

        if (! $order) {
            return ['success' => false, 'error' => 'Pedido nao encontrado.'];
        }

        if ($item->isCancelled()) {
            return ['success' => false, 'error' => 'Item ja cancelado.'];
        }

        if ($order->isBillClosed()) {
            return ['success' => false, 'error' => 'Conta ja fechada: apenas administradores podem alterar o historico.'];
        }

        if ($order->hasPayment() && $actorRole !== self::ACTOR_ADMIN) {
            return ['success' => false, 'error' => 'Pagamento ja efetuado: apenas administradores podem alterar o historico do pedido.'];
        }

        $orderCancelled = false;

        DB::transaction(function () use ($item, $order, $actor, $reason, &$orderCancelled) {
            $wasDelivered = $order->isDelivered();

            if (! $wasDelivered && ! $item->is_points_item) {
                try {
                    $this->stockService->returnItemStock($item, $actor->id);
                } catch (\Throwable $e) {
                    Log::error('Erro ao devolver estoque por item cancelado', [
                        'item_id' => $item->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $item->update([
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
            ]);

            $order->decrement('total', (float) $item->price * (int) $item->quantity);

            if ($item->is_points_item && $item->points_cost) {
                try {
                    $this->pointsService->refundPointsForItem($item->fresh());
                    $order->decrement('points_spent', (int) $item->points_cost);
                } catch (\Throwable $e) {
                    Log::error('Erro ao devolver pontos por item cancelado', [
                        'item_id' => $item->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $remainingActive = $order->items()->whereNull('cancelled_at')->count();
            if ($remainingActive === 0 && ! $order->isBillClosed()) {
                $order->update([
                    'status' => 'cancelado',
                    'cancelled_by' => $actor->id,
                    'cancellation_reason' => $reason ? substr($reason, 0, 255) : null,
                ]);
                $orderCancelled = true;
                $this->handlePointsOnCancellation($order);
            }
        });

        $this->auditService->log(
            $orderCancelled ? 'order.cancelled' : 'order.item_cancelled',
            $orderCancelled
                ? "Pedido #{$order->id} cancelado (sem itens) por {$actor->roleLabel()}"
                : "Item #{$item->id} do pedido #{$order->id} cancelado por {$actor->roleLabel()}",
            ['order_id' => $order->id, 'item_id' => $item->id, 'actor_id' => $actor->id],
            $order->tenant,
            'order',
            (string) $order->id
        );

        return ['success' => true, 'order_cancelled' => $orderCancelled];
    }

    /**
     * Estorna pagamentos recebidos (Payment + OrderPayment) e cancela
     * cobrancas pendentes. Preserva o historico: nada e apagado.
     */
    public function refundPayments(Order $order, User $admin, string $reason): float
    {
        $total = 0.0;

        $order->payments()->where('status', 'paid')->get()->each(function (Payment $payment) use ($admin, $reason, &$total) {
            $payment->markRefunded($admin->id, $reason);
            $total += (float) $payment->amount;
        });

        $order->orderPayments()->where('status', 'paid')->get()->each(function (OrderPayment $payment) use (&$total) {
            $payment->update(['status' => 'refunded']);
            $total += (float) ($payment->amount_cents / 100);
        });

        $order->orderPayments()
            ->whereIn('status', ['pending', 'processing'])
            ->update(['status' => 'cancelled']);

        if ($total > 0) {
            $order->update(['payment_status' => 'refunded']);
        }

        return $total;
    }

    private function handlePointsOnCancellation(Order $order): void
    {
        try {
            $this->pointsService->reversePointsForOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Erro ao estornar pontos no cancelamento', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->pointsService->refundSpentPointsForOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Erro ao devolver pontos gastos no cancelamento', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
        }
    }
}
