<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaasPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaasPlanFactory extends Factory
{
    protected $model = SaasPlan::class;

    public function definition(): array
    {
        return [
            'name' => 'Plano ' . fake()->words(2, true),
            'slug' => Str::slug(fake()->words(2, true)),
            'price_cents' => fake()->randomElement([0, 4990, 9790, 19990]),
            'interval' => 'month',
            'features_json' => ['max_tables' => 10],
            'is_active' => true,
        ];
    }
}
