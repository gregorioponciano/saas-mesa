<?php

namespace Database\Factories;

use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TableFactory extends Factory
{
    protected $model = Table::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'number' => (string) fake()->unique()->numberBetween(1, 99),
            'capacity' => fake()->numberBetween(2, 10),
            'status' => fake()->randomElement(['free', 'occupied', 'reserved']),
        ];
    }
}
