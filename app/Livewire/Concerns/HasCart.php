<?php

namespace App\Livewire\Concerns;

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

    protected function addCartItem(int $productId, string $productName, float $price, array $selectedOptions = [], int $quantity = 1): string
    {
        $key = $productId . '-' . md5(json_encode($selectedOptions));

        if (isset($this->cartItems[$key])) {
            $this->cartItems[$key]['quantity'] += $quantity;
        } else {
            $selected = collect($selectedOptions);
            $optionsTotal = $selected->sum('price_additional');
            $attributePriceTotal = $selected->pluck('attribute_id')->unique()->sum(fn($id) =>
                (float) ($selected->firstWhere('attribute_id', $id)['attribute_price'] ?? 0)
            );
            $this->cartItems[$key] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'base_price' => $price,
                'options' => $selectedOptions,
                'options_total' => $optionsTotal,
                'attribute_price_total' => $attributePriceTotal,
                'unit_price' => $price + $optionsTotal + $attributePriceTotal,
                'quantity' => $quantity,
            ];
        }

        $this->persistCartToSession();

        return $key;
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
