<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderPaymentFactory extends Factory
{
    protected $model = OrderPayment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'tenant_id' => Tenant::factory(),
            'amount_cents' => fake()->numberBetween(1000, 50000),
            'method' => 'pix',
            'status' => 'pending',
            'idempotency_key' => Str::uuid()->toString(),
            'expires_at' => now()->addHour(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
