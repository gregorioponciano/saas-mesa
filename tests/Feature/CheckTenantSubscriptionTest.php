<?php

declare(strict_types=1);

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\User;

function makeTenantForSubscriptionTest(string $status, string $plan): array
{
    $tenant = Tenant::create([
        'name' => 'Empresa Sub '.fake()->unique()->word(),
        'email' => fake()->unique()->safeEmail(),
        'slug' => fake()->unique()->slug(),
        'plan' => $plan,
        'status' => $status,
    ]);

    $admin = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'admin',
    ]);

    return [$tenant, $admin];
}

test('tenant com sub trial e pending antiga nao e bloqueado', function () {
    [$tenant, $admin] = makeTenantForSubscriptionTest('trial', Tenant::PLAN_PAID);

    SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => SaasPlan::factory()->create()->id,
        'status' => 'pending',
    ]);

    SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => SaasPlan::factory()->create()->id,
        'status' => 'trial',
        'trial_ends_at' => now()->addDays(7),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/settings/efi-credentials')
        ->assertOk();
});

test('tenant com apenas sub pending e bloqueado com payment_pending', function () {
    [$tenant, $admin] = makeTenantForSubscriptionTest('trial', Tenant::PLAN_PAID);

    SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => SaasPlan::factory()->create()->id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/settings/efi-credentials')
        ->assertStatus(402)
        ->assertJsonPath('error', 'payment_pending');
});

test('tenant pago e ativo com sub pending nao e bloqueado', function () {
    [$tenant, $admin] = makeTenantForSubscriptionTest('active', Tenant::PLAN_PAID);

    SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => SaasPlan::factory()->create()->id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/settings/efi-credentials')
        ->assertOk();
});
