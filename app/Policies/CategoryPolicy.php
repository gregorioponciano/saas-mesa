<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id !== null;
    }

    public function view(User $user, Category $category): bool
    {
        return $user->isSuperAdmin() || $category->tenant_id === $user->tenant_id;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isSuperAdmin() || $category->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isSuperAdmin() || $category->tenant_id === $user->tenant_id;
    }
}
