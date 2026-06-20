<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'code' => strtoupper(fake()->bothify('???####')),
            'discount_type' => fake()->randomElement(['percentage', 'fixed']),
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'min_order_value' => fake()->randomFloat(2, 10, 100),
            'max_uses' => fake()->numberBetween(10, 100),
            'used_count' => 0,
            'active' => true,
            'expires_at' => fake()->dateTimeBetween('+1 month', '+6 months'),
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attrs) => [
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomFloat(2, 5, 30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attrs) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
