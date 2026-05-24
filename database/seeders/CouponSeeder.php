<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'classic-burger-artisan')->first();

        if (!$tenant) {
            return;
        }

        Coupon::updateOrCreate(
            ['code' => 'BEMVINDO10', 'tenant_id' => $tenant->id],
            [
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_order_value' => null,
                'max_uses' => 100,
                'used_count' => 0,
                'active' => true,
                'expires_at' => now()->addYear(),
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'FRETE5', 'tenant_id' => $tenant->id],
            [
                'discount_type' => 'fixed',
                'discount_value' => 5,
                'min_order_value' => 20,
                'max_uses' => 50,
                'used_count' => 0,
                'active' => true,
                'expires_at' => now()->addMonths(6),
            ]
        );
    }
}
