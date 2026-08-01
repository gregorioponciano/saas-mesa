<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id !== null;
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->isSuperAdmin() || $coupon->tenant_id === $user->tenant_id;
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->isSuperAdmin() || $coupon->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->isSuperAdmin() || $coupon->tenant_id === $user->tenant_id;
    }
}
