<?php

namespace App\Services;

use App\Models\CustomerPoint;
use App\Models\LoyaltyConfig;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointsTransaction;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointsService
{
    public function isPointsActive(Tenant $tenant): bool
    {
        if (! $tenant->hasFeature('programa_fidelidade')) {
            return false;
        }

        $config = LoyaltyConfig::forTenant($tenant);

        return $config->points_enabled;
    }

    public function grantPointsForOrder(Order $order): bool
    {
        if (! $order->user_id) {
            return false;
        }

        $tenant = $order->tenant;

        if (! $this->isPointsActive($tenant)) {
            return false;
        }

        $idempotencyKey = "order_points_{$order->id}";

        $earnedTransaction = PointsTransaction::where('order_id', $order->id)
            ->where('type', PointsTransaction::TYPE_EARNED)
            ->latest('id')
            ->first();

        $reversal = null;

        if ($earnedTransaction) {
            $reversal = PointsTransaction::where('order_id', $order->id)
                ->where('type', PointsTransaction::TYPE_REVERSED)
                ->where('id', '>', $earnedTransaction->id)
                ->latest('id')
                ->first();

            if (! $reversal) {
                return true;
            }

            // Reabertura de conta: o estorno existente invalida a chave
            // anterior, permitindo conceder os pontos novamente no novo
            // fechamento (chave nova por estorno).
            $idempotencyKey = "order_points_{$order->id}_regrant_{$reversal->id}";
        }

        $alreadyProcessed = PointsTransaction::where('idempotency_key', $idempotencyKey)->exists();

        if ($alreadyProcessed) {
            return true;
        }

        $netAmount = (float) $order->total - (float) ($order->delivery_cost ?? 0);

        if ($netAmount <= 0) {
            return false;
        }

        $config = LoyaltyConfig::forTenant($tenant);
        $percentage = $config->points_percentage ?? 1;

        $netAmountCents = (int) round($netAmount * 100);
        $pointsToAward = (int) floor($netAmountCents * $percentage / 100);

        if ($pointsToAward <= 0) {
            return false;
        }

        DB::transaction(function () use ($tenant, $order, $pointsToAward, $idempotencyKey) {
            $pointsRecord = CustomerPoint::firstOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $order->user_id],
                ['balance' => 0]
            );

            $pointsRecord->increment('balance', $pointsToAward);

            PointsTransaction::create([
                'tenant_id' => $tenant->id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'points' => $pointsToAward,
                'type' => PointsTransaction::TYPE_EARNED,
                'description' => "Pedido #{$order->id} - {$pointsToAward} pontos",
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        Log::info('Pontos concedidos', [
            'tenant_id' => $tenant->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'points' => $pointsToAward,
            'idempotency_key' => $idempotencyKey,
        ]);

        return true;
    }

    public function reversePointsForOrder(Order $order): bool
    {
        if (! $order->user_id) {
            return false;
        }

        $earnedTransaction = PointsTransaction::where('order_id', $order->id)
            ->where('type', PointsTransaction::TYPE_EARNED)
            ->first();

        if (! $earnedTransaction) {
            return false;
        }

        $idempotencyKey = "order_reversal_{$order->id}";

        $alreadyReversed = PointsTransaction::where('idempotency_key', $idempotencyKey)->exists();

        if ($alreadyReversed) {
            return true;
        }

        DB::transaction(function () use ($order, $earnedTransaction, $idempotencyKey) {
            $pointsRecord = CustomerPoint::where('tenant_id', $order->tenant_id)
                ->where('user_id', $order->user_id)
                ->first();

            if ($pointsRecord) {
                $pointsRecord->decrement('balance', $earnedTransaction->points);
            }

            PointsTransaction::create([
                'tenant_id' => $order->tenant_id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'points' => -$earnedTransaction->points,
                'type' => PointsTransaction::TYPE_REVERSED,
                'description' => "Estorno Pedido #{$order->id} - {$earnedTransaction->points} pontos",
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        Log::info('Pontos estornados', [
            'tenant_id' => $order->tenant_id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'points' => -$earnedTransaction->points,
        ]);

        return true;
    }

    public function refundSpentPointsForOrder(Order $order): bool
    {
        if (! $order->user_id) {
            return false;
        }

        $spentTransaction = PointsTransaction::where('order_id', $order->id)
            ->where('type', PointsTransaction::TYPE_SPENT)
            ->first();

        if (! $spentTransaction) {
            return false;
        }

        $idempotencyKey = "order_refund_spent_{$order->id}";

        $alreadyRefunded = PointsTransaction::where('idempotency_key', $idempotencyKey)->exists();

        if ($alreadyRefunded) {
            return true;
        }

        $pointsToRefund = max(0, (int) $order->points_spent);

        if ($pointsToRefund <= 0) {
            return true;
        }

        DB::transaction(function () use ($order, $pointsToRefund, $idempotencyKey) {
            $pointsRecord = CustomerPoint::where('tenant_id', $order->tenant_id)
                ->where('user_id', $order->user_id)
                ->first();

            if ($pointsRecord) {
                $pointsRecord->increment('balance', $pointsToRefund);
            }

            PointsTransaction::create([
                'tenant_id' => $order->tenant_id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'points' => $pointsToRefund,
                'type' => PointsTransaction::TYPE_REFUNDED,
                'description' => "Devolucao Pedido #{$order->id} - {$pointsToRefund} pontos",
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        Log::info('Pontos devolvidos por cancelamento/reabertura', [
            'tenant_id' => $order->tenant_id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'points' => $pointsToRefund,
        ]);

        return true;
    }

    public function refundPointsForItem(OrderItem $item): bool
    {
        $order = $item->order;

        if (! $order || ! $order->user_id) {
            return false;
        }

        if (! $item->is_points_item || ! $item->points_cost) {
            return false;
        }

        $idempotencyKey = "item_refund_{$item->id}";

        $alreadyRefunded = PointsTransaction::where('idempotency_key', $idempotencyKey)->exists();

        if ($alreadyRefunded) {
            return true;
        }

        DB::transaction(function () use ($item, $order, $idempotencyKey) {
            $pointsRecord = CustomerPoint::where('tenant_id', $order->tenant_id)
                ->where('user_id', $order->user_id)
                ->first();

            if ($pointsRecord) {
                $pointsRecord->increment('balance', $item->points_cost);
            }

            PointsTransaction::create([
                'tenant_id' => $order->tenant_id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'points' => $item->points_cost,
                'type' => PointsTransaction::TYPE_REFUNDED,
                'description' => "Devolucao Item #{$item->id} - {$item->points_cost} pontos",
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        Log::info('Pontos devolvidos por cancelamento de item', [
            'tenant_id' => $order->tenant_id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'item_id' => $item->id,
            'points' => $item->points_cost,
        ]);

        return true;
    }

    public function disableForTenant(Tenant $tenant): void
    {
        $config = LoyaltyConfig::forTenant($tenant);

        if ($config->points_enabled) {
            $config->update(['points_enabled' => false]);

            Log::info('Sistema de pontos desativado automaticamente', [
                'tenant_id' => $tenant->id,
                'reason' => 'downgrade_plan',
            ]);
        }
    }

    public function getCustomerBalance(Tenant $tenant, User $user): int
    {
        return CustomerPoint::getBalance($tenant, $user);
    }

    public function arePointsVisibleForCustomer(Tenant $tenant): bool
    {
        return $this->isPointsActive($tenant);
    }

    public function pointsToMoney(int $points): float
    {
        return round($points / 100, 2);
    }

    public function moneyToPoints(float $amount): int
    {
        return (int) floor($amount * 100);
    }

    public function spendPoints(
        Tenant $tenant,
        User $user,
        int $pointsToSpend,
        ?Order $order = null,
        string $description = ''
    ): array {
        if (! $this->isPointsActive($tenant)) {
            return ['success' => false, 'message' => 'Sistema de pontos inativo.'];
        }

        if ($pointsToSpend <= 0) {
            return ['success' => false, 'message' => 'Quantidade de pontos invalida.'];
        }

        $balance = $this->getCustomerBalance($tenant, $user);

        if ($balance < $pointsToSpend) {
            return ['success' => false, 'message' => 'Saldo insuficiente.'];
        }

        $idempotencyKey = $order
            ? "points_spent_order_{$order->id}_{$user->id}"
            : "points_spent_{$tenant->id}_{$user->id}_".now()->timestamp;

        $alreadyProcessed = $order && PointsTransaction::where('idempotency_key', $idempotencyKey)->exists();

        if ($alreadyProcessed) {
            return ['success' => true, 'message' => 'Pontos ja utilizados neste pedido.', 'money_value' => $this->pointsToMoney($pointsToSpend)];
        }

        $moneyValue = $this->pointsToMoney($pointsToSpend);

        DB::transaction(function () use ($tenant, $user, $pointsToSpend, $moneyValue, $order, $description, $idempotencyKey) {
            $pointsRecord = CustomerPoint::where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $pointsRecord || $pointsRecord->balance < $pointsToSpend) {
                throw new \RuntimeException('Saldo insuficiente no momento da transacao.');
            }

            $pointsRecord->decrement('balance', $pointsToSpend);

            $desc = $description ?: ($order
                ? "Resgate de {$pointsToSpend} pontos no Pedido #{$order->id} (R$ ".number_format($moneyValue, 2, ',', '.').')'
                : "Resgate de {$pointsToSpend} pontos (R$ ".number_format($moneyValue, 2, ',', '.').')');

            PointsTransaction::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'order_id' => $order?->id,
                'points' => -$pointsToSpend,
                'type' => PointsTransaction::TYPE_SPENT,
                'description' => $desc,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        Log::info('Pontos resgatados', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'points_spent' => $pointsToSpend,
            'money_value' => $moneyValue,
        ]);

        return [
            'success' => true,
            'message' => "{$pointsToSpend} pontos resgatados! R$ ".number_format($moneyValue, 2, ',', '.').' de desconto.',
            'money_value' => $moneyValue,
        ];
    }

    public function getMaxSpendablePoints(Tenant $tenant, User $user, float $orderValue): int
    {
        $balance = $this->getCustomerBalance($tenant, $user);
        $maxPointsByValue = $this->moneyToPoints($orderValue);

        return min($balance, $maxPointsByValue);
    }

    public function getTransactionHistory(Tenant $tenant, User $user, int $limit = 20): array
    {
        return PointsTransaction::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->latest()
            ->take($limit)
            ->get()
            ->toArray();
    }
}
