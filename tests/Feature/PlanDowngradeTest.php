<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\LoyaltyConfig;
use App\Models\Product;
use App\Models\SaasPlan;
use App\Models\Table;
use App\Models\Tenant;
use App\Services\PointsService;

test('TenantObserver desativa fidelidade ao sair do plano paid', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    LoyaltyConfig::forTenant($tenant)->update(['points_enabled' => true]);
    expect(LoyaltyConfig::forTenant($tenant)->points_enabled)->toBeTrue();

    $tenant->update(['plan' => Tenant::PLAN_FREE]);

    expect(LoyaltyConfig::forTenant($tenant)->points_enabled)->toBeFalse();
});

test('TenantObserver nao reativa fidelidade sozinho ao voltar para o plano paid', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    LoyaltyConfig::forTenant($tenant)->update(['points_enabled' => true]);
    $tenant->update(['plan' => Tenant::PLAN_FREE]);
    expect(LoyaltyConfig::forTenant($tenant)->points_enabled)->toBeFalse();

    $tenant->update(['plan' => Tenant::PLAN_PAID]);

    expect(LoyaltyConfig::forTenant($tenant)->points_enabled)->toBeFalse();
    expect(LoyaltyConfig::forTenant($tenant)->canEnable())->toBeTrue();
});

test('upgrade free para paid nao desativa fidelidade configurada pelo admin', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);

    LoyaltyConfig::forTenant($tenant)->update(['points_enabled' => true]);

    $tenant->update(['plan' => Tenant::PLAN_PAID]);

    expect(LoyaltyConfig::forTenant($tenant)->points_enabled)->toBeTrue();
    expect(app(PointsService::class)->arePointsVisibleForCustomer($tenant))->toBeTrue();
});

test('tenant com mesas acima do limite do novo plano degrada de forma previsivel', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'max_tables' => 50, 'status' => 'active']);

    Table::factory()->count(5)->create(['tenant_id' => $tenant->id]);

    $tenant->update(['plan' => Tenant::PLAN_FREE, 'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE]]);

    $tenant->refresh();

    expect($tenant->canAddTable())->toBeFalse();
    expect($tenant->hasHiddenTables())->toBeTrue();
    expect($tenant->hiddenTablesCount())->toBe(3);
    expect($tenant->maxTablesAllowed())->toBe(2);
    expect($tenant->manageableTables()->count())->toBe(2);
    expect(Table::where('tenant_id', $tenant->id)->count())->toBe(5);
});

test('tenant free continua listando todas as mesas e cria mesas ate o limite', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE], 'status' => 'active']);

    Table::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    expect($tenant->canAddTable())->toBeFalse();
    expect($tenant->hasHiddenTables())->toBeFalse();
    expect($tenant->manageableTables()->count())->toBe(2);
});

test('criar produtos alem do limite do plano nao trava o sistema', function () {
    seedPlans();
    $freePlan = SaasPlan::where('slug', 'free')->first();

    expect($freePlan->features_json['max_products'])->toBe(20);

    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'max_tables' => 2, 'status' => 'active']);
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);

    for ($i = 0; $i < 21; $i++) {
        Product::factory()->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);
    }

    expect(Product::where('tenant_id', $tenant->id)->count())->toBe(21);
    expect($tenant->products()->count())->toBe(21);
});

test('downgrade com fidelidade falhando nao impede a troca de plano', function () {
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    LoyaltyConfig::forTenant($tenant)->update(['points_enabled' => true]);

    $this->mock(PointsService::class, function ($mock) {
        $mock->shouldReceive('disableForTenant')
            ->once()
            ->andThrow(new RuntimeException('Falha ao desativar pontos'));
    });

    $tenant->update(['plan' => Tenant::PLAN_FREE]);

    expect($tenant->fresh()->plan)->toBe(Tenant::PLAN_FREE);
    expect($tenant->fresh()->status)->toBe('active');
});
