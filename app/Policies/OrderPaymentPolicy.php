<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrderPayment;
use App\Models\User;

class OrderPaymentPolicy
{
    public function view(User $user, OrderPayment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function refund(User $user, OrderPayment $payment): bool
    {
        return $user->isAdmin() && $user->tenant_id === $payment->tenant_id;
    }
}
