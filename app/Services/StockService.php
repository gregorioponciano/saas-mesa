<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    public function validateStockForCartItems(array $cartItems, int $tenantId): array
    {
        $errors = [];

        $productIds = collect($cartItems)->pluck('product_id')->unique()->values()->toArray();
        $products = Product::where('tenant_id', $tenantId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($cartItems as $key => $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];

            $product = $products->get($productId);

            if (! $product) {
                $errors[$key] = 'Produto nao encontrado.';

                continue;
            }

            if ($product->stock < $quantity) {
                $errors[$key] = "{$product->name} possui apenas {$product->stock} unidade(s) em estoque, mas voce pediu {$quantity}.";
            }
        }

        return $errors;
    }

    public function deductOrderStock(Order $order, int $userId): array
    {
        $results = [];

        foreach ($order->items as $item) {
            if ($item->is_points_item) {
                continue;
            }

            $result = $this->deductStock(
                $item->product_id,
                $item->quantity,
                $order->tenant_id,
                $order->id,
                $userId,
                'sale',
                "Venda - Pedido #{$order->id}"
            );

            $results[$item->id] = $result;
        }

        return $results;
    }

    public function returnOrderStock(Order $order, int $userId): array
    {
        $results = [];

        foreach ($order->items as $item) {
            if ($item->is_points_item || $item->isCancelled()) {
                continue;
            }

            $result = $this->returnStock(
                $item->product_id,
                $item->quantity,
                $order->tenant_id,
                $order->id,
                $userId,
                'cancellation',
                "Cancelamento - Pedido #{$order->id}"
            );

            $results[$item->id] = $result;
        }

        return $results;
    }

    public function returnItemStock(OrderItem $item, int $userId): bool
    {
        if ($item->is_points_item || $item->isCancelled()) {
            return false;
        }

        return $this->returnStock(
            $item->product_id,
            $item->quantity,
            $item->order->tenant_id,
            $item->order_id,
            $userId,
            'cancellation',
            "Cancelamento de item - Pedido #{$item->order_id}"
        );
    }

    public function deductStock(
        ?int $productId,
        int $quantity,
        int $tenantId,
        ?int $orderId = null,
        ?int $userId = null,
        string $type = 'sale',
        ?string $description = null
    ): bool {
        if (! $productId || $quantity <= 0) {
            return false;
        }

        $idempotencyKey = "stock_{$type}_order_{$orderId}_product_{$productId}";

        $alreadyProcessed = StockMovement::where('idempotency_key', $idempotencyKey)->exists();
        if ($alreadyProcessed) {
            return true;
        }

        return DB::transaction(function () use ($productId, $quantity, $tenantId, $orderId, $userId, $type, $description, $idempotencyKey) {
            $product = Product::where('tenant_id', $tenantId)
                ->where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw new \RuntimeException("Produto #{$productId} nao encontrado.");
            }

            if ($product->stock < $quantity) {
                throw new \RuntimeException(
                    "Estoque insuficiente para {$product->name}. Disponivel: {$product->stock}, solicitado: {$quantity}."
                );
            }

            $stockBefore = $product->stock;
            $product->decrement('stock', $quantity);
            $product->refresh();

            StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'quantity' => -$quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock,
                'type' => $type,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
            ]);

            Log::info('Estoque deduzido', [
                'product_id' => $productId,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock,
                'order_id' => $orderId,
                'type' => $type,
            ]);

            return true;
        });
    }

    public function returnStock(
        ?int $productId,
        int $quantity,
        int $tenantId,
        ?int $orderId = null,
        ?int $userId = null,
        string $type = 'return',
        ?string $description = null
    ): bool {
        if (! $productId || $quantity <= 0) {
            return false;
        }

        $idempotencyKey = "stock_{$type}_order_{$orderId}_product_{$productId}";

        $alreadyProcessed = StockMovement::where('idempotency_key', $idempotencyKey)->exists();
        if ($alreadyProcessed) {
            return true;
        }

        return DB::transaction(function () use ($productId, $quantity, $tenantId, $orderId, $userId, $type, $description, $idempotencyKey) {
            $product = Product::where('tenant_id', $tenantId)
                ->where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw new \RuntimeException("Produto #{$productId} nao encontrado.");
            }

            $stockBefore = $product->stock;
            $product->increment('stock', $quantity);
            $product->refresh();

            StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock,
                'type' => $type,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
            ]);

            Log::info('Estoque restituido', [
                'product_id' => $productId,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock,
                'order_id' => $orderId,
                'type' => $type,
            ]);

            return true;
        });
    }

    public function getStock(int $productId, int $tenantId): int
    {
        $product = Product::where('tenant_id', $tenantId)
            ->where('id', $productId)
            ->first();

        return $product ? (int) $product->stock : 0;
    }

    public function adjustStock(
        int $productId,
        int $newStock,
        int $tenantId,
        ?int $userId = null,
        ?string $description = null
    ): bool {
        return DB::transaction(function () use ($productId, $newStock, $tenantId, $userId, $description) {
            $product = Product::where('tenant_id', $tenantId)
                ->where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw new \RuntimeException("Produto #{$productId} nao encontrado.");
            }

            $stockBefore = $product->stock;
            $difference = $newStock - $stockBefore;

            if ($newStock < 0) {
                throw new \RuntimeException('Estoque nao pode ser negativo.');
            }

            $product->update(['stock' => $newStock]);

            $type = $difference >= 0 ? 'entry' : 'exit';

            StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'user_id' => $userId,
                'quantity' => $difference,
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'type' => 'manual_adjustment',
                'description' => $description ?: "Ajuste manual: {$stockBefore} -> {$newStock}",
            ]);

            Log::info('Estoque ajustado manualmente', [
                'product_id' => $productId,
                'product_name' => $product->name,
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'user_id' => $userId,
            ]);

            return true;
        });
    }

    public function getStockMovements(int $productId, int $tenantId, int $limit = 50): array
    {
        return StockMovement::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->with('user', 'order')
            ->latest()
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function getLowStockProducts(int $tenantId, int $threshold = 5)
    {
        return Product::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->get();
    }
}
