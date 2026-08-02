<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'email' => fake()->unique()->companyEmail(),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'plan' => Tenant::PLAN_FREE,
            'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
            'status' => 'active',
        ];
    }
}
