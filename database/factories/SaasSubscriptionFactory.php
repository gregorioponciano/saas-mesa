<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaasSubscriptionFactory extends Factory
{
    protected $model = SaasSubscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => SaasPlan::factory(),
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ];
    }

    public function trial(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
    }
}
