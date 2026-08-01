<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id !== null;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isSuperAdmin() || $product->tenant_id === $user->tenant_id;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isSuperAdmin() || $product->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isSuperAdmin() || $product->tenant_id === $user->tenant_id;
    }
}
