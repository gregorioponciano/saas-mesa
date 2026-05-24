<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'total' => fake()->randomFloat(2, 20, 200),
            'payment_method' => fake()->randomElement(['pix', 'credit_card', 'cash']),
            'status' => 'entregue',
        ];
    }
}
