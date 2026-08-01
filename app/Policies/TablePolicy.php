<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Table;
use App\Models\User;

class TablePolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id !== null;
    }

    public function view(User $user, Table $table): bool
    {
        return $user->isSuperAdmin() || $table->tenant_id === $user->tenant_id;
    }

    public function update(User $user, Table $table): bool
    {
        return $user->isSuperAdmin() || $table->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Table $table): bool
    {
        return $user->isSuperAdmin() || $table->tenant_id === $user->tenant_id;
    }
}
