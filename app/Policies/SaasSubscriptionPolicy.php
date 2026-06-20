<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SaasSubscription;
use App\Models\User;

class SaasSubscriptionPolicy
{
    public function view(User $user, SaasSubscription $subscription): bool
    {
        return $user->isAdmin() || $user->tenant_id === $subscription->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SaasSubscription $subscription): bool
    {
        return $user->isAdmin() || $user->tenant_id === $subscription->tenant_id;
    }

    public function delete(User $user, SaasSubscription $subscription): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, SaasSubscription $subscription): bool
    {
        return $user->isAdmin() || $user->tenant_id === $subscription->tenant_id;
    }

    public function suspend(User $user, SaasSubscription $subscription): bool
    {
        return $user->isAdmin();
    }

    public function reactivate(User $user, SaasSubscription $subscription): bool
    {
        return $user->isAdmin();
    }
}
