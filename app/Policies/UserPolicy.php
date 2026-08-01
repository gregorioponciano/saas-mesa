<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id !== null;
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin() || $model->tenant_id === $user->tenant_id;
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin() || $model->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() || $model->tenant_id === $user->tenant_id;
    }
}
