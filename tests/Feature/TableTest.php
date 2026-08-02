<?php

use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;

test('usuario pode criar mesa', function () {
    $tenant = Tenant::factory()->create(['plan' => Tenant::PLAN_PAID, 'max_tables' => 50]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user);

    $table = Table::create([
        'tenant_id' => $tenant->id,
        'number' => '01',
        'capacity' => 4,
        'status' => 'free',
    ]);

    $this->assertDatabaseHas('tables', [
        'tenant_id' => $tenant->id,
        'number' => '01',
    ]);
});

test('mesa pertence ao tenant', function () {
    $table = Table::factory()->create();
    $this->assertInstanceOf(Tenant::class, $table->tenant);
});

test('tenant pode verificar limite de mesas no plano gratuito', function () {
    $tenant = Tenant::factory()->create([
        'plan' => Tenant::PLAN_FREE,
        'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
    ]);

    expect($tenant->canAddTable())->toBeTrue();

    Table::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    expect($tenant->canAddTable())->toBeFalse();
});

test('tenant pode verificar limite de mesas no plano premium', function () {
    $tenant = Tenant::factory()->create([
        'plan' => Tenant::PLAN_PAID,
        'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_PAID],
    ]);

    expect($tenant->canAddTable())->toBeTrue();
});

test('numero da mesa deve ser unico por tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Table::create([
        'tenant_id' => $tenant->id,
        'number' => '01',
        'capacity' => 4,
    ]);

    $this->expectException(QueryException::class);
    Table::create([
        'tenant_id' => $tenant->id,
        'number' => '01',
        'capacity' => 4,
    ]);
});
