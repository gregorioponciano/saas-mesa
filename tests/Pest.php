<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

beforeEach(function () {
    Cache::flush();
});

function createSuperAdmin(): User
{
    return User::factory()->create([
        'role' => 'superadmin',
        'tenant_id' => null,
    ]);
}

function createTenant(array $overrides = []): Tenant
{
    return Tenant::factory()->create($overrides);
}

function createTenantAdmin(Tenant $tenant, array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'role' => 'admin',
    ], $overrides));
}

function seedPlans(): void
{
    (new SaasPlanSeeder)->run();
}
