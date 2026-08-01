<?php

namespace App\Livewire\Concerns;

use App\Models\Product;
use App\Models\ProductAttributeOption;
use Illuminate\Support\Facades\Session;

trait HasCart
{
    public array $cartItems = [];

    protected function restoreCartFromSession(): void
    {
        if (property_exists($this, 'tenant') && $this->tenant) {
            $tenantId = is_object($this->tenant) ? $this->tenant->id : $this->tenant;
            $items = Session::get("cart_{$tenantId}", []);
            if (!empty($items)) {
                $this->cartItems = $items;
            }
        }
    }

    protected function persistCartToSession(): void
    {
        if (property_exists($this, 'tenant') && $this->tenant) {
            $tenantId = is_object($this->tenant) ? $this->tenant->id : $this->tenant;
            Session::put("cart_{$tenantId}", $this->cartItems);
        }
    }

    protected function resolveCartTenantId(): ?int
    {
        if (property_exists($this, 'tenant') && $this->tenant) {
            return is_object($this->tenant) ? (int) $this->tenant->id : (int) $this->tenant;
        }

        return null;
    }

    protected function resolveProductForCart(int $productId): ?Product
    {
        $tenantId = $this->resolveCartTenantId();
        if ($tenantId === null) {
            return null;
        }

        return Product::where('tenant_id', $tenantId)->find($productId);
    }

    protected function validateCartOptions(Product $product, array $selectedOptions = []): array
    {
        $validated = [];

        foreach ($selectedOptions as $option) {
            if (!is_array($option)) {
                continue;
            }

            $optionId = (int) ($option['option_id'] ?? 0);
            $attributeId = (int) ($option['attribute_id'] ?? 0);
            if ($optionId <= 0 || $attributeId <= 0) {
                continue;
            }

            $realOption = ProductAttributeOption::with('attribute')
                ->where('id', $optionId)
                ->where('product_attribute_id', $attributeId)
                ->first();

            if (!$realOption || !$realOption->attribute || (int) $realOption->attribute->product_id !== (int) $product->id) {
                continue;
            }

            $validated[] = [
                'attribute_id' => $realOption->product_attribute_id,
                'attribute_name' => $option['attribute_name'] ?? $realOption->attribute->name,
                'option_id' => $realOption->id,
                'option_name' => $option['option_name'] ?? $realOption->name,
                'price_additional' => (float) $realOption->price_additional,
                'attribute_price' => (float) $realOption->attribute->price,
            ];
        }

        return $validated;
    }

    protected function buildValidatedCartItem(Product $product, array $selectedOptions, int $quantity, array $original = []): array
    {
        $validatedOptions = $this->validateCartOptions($product, $selectedOptions);
        $optionsTotal = collect($validatedOptions)->sum('price_additional');
        $attributePriceTotal = collect($validatedOptions)
            ->pluck('attribute_id')
            ->unique()
            ->sum(fn ($id) => (float) (collect($validatedOptions)->firstWhere('attribute_id', $id)['attribute_price'] ?? 0));

        $isPointsItem = (bool) ($original['is_points_item'] ?? false);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'base_price' => (float) $product->price,
            'options' => $validatedOptions,
            'options_total' => $optionsTotal,
            'attribute_price_total' => $attributePriceTotal,
            'unit_price' => $isPointsItem ? 0.0 : (float) $product->price + $optionsTotal + $attributePriceTotal,
            'quantity' => $quantity,
            'is_points_item' => $isPointsItem,
            'points_cost' => $isPointsItem ? (int) round((float) $product->points_price) : ($original['points_cost'] ?? null),
        ];
    }

    protected function addCartItem(int $productId, string $productName, float $price, array $selectedOptions = [], int $quantity = 1): string
    {
        $product = $this->resolveProductForCart($productId);
        if (!$product) {
            return '';
        }

        $validatedOptions = $this->validateCartOptions($product, $selectedOptions);
        $key = $product->id . '-' . md5(json_encode($validatedOptions));

        if (isset($this->cartItems[$key])) {
            $this->cartItems[$key]['quantity'] += $quantity;
        } else {
            $this->cartItems[$key] = $this->buildValidatedCartItem($product, $validatedOptions, $quantity);
        }

        $this->persistCartToSession();

        return $key;
    }

    protected function revalidateCartAgainstDatabase(): void
    {
        $tenantId = $this->resolveCartTenantId();
        if ($tenantId === null || empty($this->cartItems)) {
            return;
        }

        $productIds = array_unique(array_column($this->cartItems, 'product_id'));
        $products = Product::where('tenant_id', $tenantId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($this->cartItems as $key => $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            if (!$product) {
                unset($this->cartItems[$key]);
                continue;
            }

            $this->cartItems[$key] = $this->buildValidatedCartItem(
                $product,
                $item['options'] ?? [],
                (int) ($item['quantity'] ?? 1),
                $item
            );
        }

        $this->persistCartToSession();
    }

    protected function removeCartItem(string $key): void
    {
        unset($this->cartItems[$key]);
        $this->persistCartToSession();
    }

    protected function adjustCartQuantity(string $key, int $delta): void
    {
        if (isset($this->cartItems[$key])) {
            $newQty = $this->cartItems[$key]['quantity'] + $delta;
            if ($newQty <= 0) {
                unset($this->cartItems[$key]);
            } else {
                $this->cartItems[$key]['quantity'] = $newQty;
            }
        }
        $this->persistCartToSession();
    }

    protected function calcCartTotal(): float
    {
        return collect($this->cartItems)->sum(fn($i) => $i['unit_price'] * $i['quantity']);
    }

    protected function calcCartItemsCount(): int
    {
        return collect($this->cartItems)->sum('quantity');
    }

    protected function resetCart(): void
    {
        $this->cartItems = [];

        if (property_exists($this, 'tenant') && $this->tenant) {
            $tenantId = is_object($this->tenant) ? $this->tenant->id : $this->tenant;
            Session::forget("cart_{$tenantId}");
        }
    }
}
