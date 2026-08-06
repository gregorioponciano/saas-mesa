<?php

declare(strict_types=1);

use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\UserManager;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\LoyaltyConfig;
use App\Models\Product;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\PointsService;
use App\Services\TenantBackupService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

test('edicao de limite pelo superadmin reflete imediatamente nos tenants existentes', function () {
    seedPlans();
    $superadmin = createSuperAdmin();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    expect($tenant->maxTablesAllowed())->toBe(50);
    expect($tenant->currentPlan()?->slug)->toBe('premium');

    $this->actingAs($superadmin)
        ->putJson('/api/superadmin/plans/'.$premium->id, [
            'features_json' => [
                'max_tables' => 60,
                'max_products' => 999,
                'max_users' => 20,
                'programa_fidelidade' => true,
            ],
        ])
        ->assertOk();

    expect($tenant->fresh()->maxTablesAllowed())->toBe(60);

    $audit = AuditLog::where('action', 'plan.update')->latest()->first();
    expect($audit)->not->toBeNull();
    expect($audit->data['before']['features_json']['max_tables'])->toBe(50);
    expect($audit->data['after']['features_json']['max_tables'])->toBe(60);
});

test('override individual max_tables tem precedencia sobre o plano', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'max_tables' => 80, 'status' => 'active']);

    expect($tenant->maxTablesAllowed())->toBe(80);

    $freeOverride = createTenant(['plan' => Tenant::PLAN_FREE, 'max_tables' => 3, 'status' => 'active']);
    expect($freeOverride->maxTablesAllowed())->toBe(3);
});

test('canCreateProduct respeita o limite do plano dinamicamente', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);

    expect($tenant->maxProductsAllowed())->toBe(20);
    expect($tenant->canCreateProduct())->toBeTrue();

    Product::factory()->count(19)->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);

    expect($tenant->canCreateProduct())->toBeTrue();

    Product::factory()->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);

    expect($tenant->canCreateProduct())->toBeFalse();
});

test('canCreateUser respeita o limite do plano dinamicamente', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    createTenantAdmin($tenant);

    expect($tenant->maxUsersAllowed())->toBe(2);
    expect($tenant->canCreateUser())->toBeTrue();

    User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'atendente']);

    expect($tenant->canCreateUser())->toBeFalse();
});

test('MenuManager bloqueia criacao de produto alem do limite do plano', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    $user = createTenantAdmin($tenant);
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);

    Product::factory()->count(20)->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);

    Livewire::actingAs($user)
        ->test(MenuManager::class)
        ->set('productName', 'Burguer Bloqueado')
        ->set('productPrice', 25.0)
        ->set('productStatus', 'active')
        ->set('productCategoryId', $category->id)
        ->call('saveProduct')
        ->assertHasErrors('productName');

    expect(Product::where('tenant_id', $tenant->id)->count())->toBe(20);
});

test('MenuManager permite criar ate atingir o limite do plano', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    $user = createTenantAdmin($tenant);
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);

    Product::factory()->count(19)->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);

    Livewire::actingAs($user)
        ->test(MenuManager::class)
        ->set('productName', 'Burguer Permitido')
        ->set('productPrice', 25.0)
        ->set('productStatus', 'active')
        ->set('productCategoryId', $category->id)
        ->call('saveProduct')
        ->assertHasNoErrors();

    expect(Product::where('tenant_id', $tenant->id)->count())->toBe(20);
});

test('UserManager bloqueia criacao de usuario alem do limite do plano', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    $user = createTenantAdmin($tenant);
    User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'atendente']);

    Livewire::actingAs($user)
        ->test(UserManager::class)
        ->set('name', 'Terceiro Usuario')
        ->set('email', 'terceiro@localhost.dev')
        ->set('role', 'atendente')
        ->set('password', 'segredo123')
        ->set('passwordConfirmation', 'segredo123')
        ->call('save')
        ->assertHasErrors('email');

    expect(User::where('tenant_id', $tenant->id)->count())->toBe(2);
});

test('hasFeature segue features_json editado pelo superadmin', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $free = SaasPlan::where('slug', 'free')->first();

    $paid = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $gratuito = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);

    expect($paid->hasFeature('programa_fidelidade'))->toBeTrue();
    expect($paid->hasFeature('backup_retention_days'))->toBeTrue();
    expect($gratuito->hasFeature('programa_fidelidade'))->toBeFalse();
    expect($gratuito->hasFeature('backup_retention_days'))->toBeFalse();

    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin)
        ->putJson('/api/superadmin/plans/'.$premium->id, [
            'features_json' => [
                'max_tables' => 50,
                'max_products' => 999,
                'max_users' => 20,
                'programa_fidelidade' => false,
            ],
        ])
        ->assertOk();

    expect($paid->fresh()->hasFeature('programa_fidelidade'))->toBeFalse();

    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin)
        ->putJson('/api/superadmin/plans/'.$free->id, [
            'features_json' => [
                'max_tables' => 2,
                'max_products' => 20,
                'max_users' => 2,
                'programa_fidelidade' => false,
                'backup_retention_days' => 30,
            ],
        ])
        ->assertOk();

    expect($gratuito->fresh()->hasFeature('backup_retention_days'))->toBeTrue();
});

test('fidelidade: canEnable e isPointsActive dependem da feature do plano', function () {
    seedPlans();
    $paid = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $gratuito = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);

    LoyaltyConfig::forTenant($paid)->update(['points_enabled' => true]);
    LoyaltyConfig::forTenant($gratuito)->update(['points_enabled' => true]);

    expect(LoyaltyConfig::forTenant($paid)->canEnable())->toBeTrue();
    expect(LoyaltyConfig::forTenant($gratuito)->canEnable())->toBeFalse();
    expect(app(PointsService::class)->isPointsActive($paid))->toBeTrue();
    expect(app(PointsService::class)->isPointsActive($gratuito))->toBeFalse();
});

test('backup: retencao e quantidade maxima vem do plano do tenant', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $free = SaasPlan::where('slug', 'free')->first();
    $service = app(TenantBackupService::class);

    $paid = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    SaasSubscription::create([
        'tenant_id' => $paid->id,
        'plan_id' => $premium->id,
        'status' => 'active',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    expect($service->maxBackupsForTenant($paid))->toBe(30);
    expect($service->retentionDaysForTenant($paid))->toBeNull();

    $gratuito = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    SaasSubscription::create([
        'tenant_id' => $gratuito->id,
        'plan_id' => $free->id,
        'status' => 'active',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    expect($service->maxBackupsForTenant($gratuito))->toBe(3);
    expect($service->retentionDaysForTenant($gratuito))->toBe(7);
});

test('downgrade limpa o override e volta ao limite do plano', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'max_tables' => 80, 'status' => 'active']);

    expect($tenant->maxTablesAllowed())->toBe(80);

    $tenant->update(['plan' => Tenant::PLAN_FREE, 'max_tables' => null]);

    expect($tenant->maxTablesAllowed())->toBe(2);
    expect($tenant->canAddTable())->toBeTrue();
});

test('mesas acima do limite escondem mesmo em plano pago com limite menor', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    Table::factory()->count(60)->create(['tenant_id' => $tenant->id]);

    expect($tenant->maxTablesAllowed())->toBe(50);
    expect($tenant->hasHiddenTables())->toBeTrue();
    expect($tenant->hiddenTablesCount())->toBe(10);
    expect($tenant->manageableTables()->count())->toBe(50);
    expect(Table::where('tenant_id', $tenant->id)->count())->toBe(60);
});

test('limite lido do plano fica em cache e e invalidado pelo superadmin', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    $tenant->currentPlan();
    expect(Cache::has(SaasPlan::planCacheKey('premium')))->toBeTrue();

    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/plans/'.$premium->id, [
            'features_json' => ['max_tables' => 75, 'max_products' => 999, 'max_users' => 20],
        ])
        ->assertOk();

    expect(Cache::has(SaasPlan::planCacheKey('premium')))->toBeFalse();
    expect($tenant->fresh()->maxTablesAllowed())->toBe(75);
});

test('update mantem o slug do plano quando o nome nao muda', function () {
    seedPlans();
    $free = SaasPlan::where('slug', 'free')->first();

    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/plans/'.$free->id, [
            'name' => 'Gratuito',
            'features_json' => ['max_tables' => 6, 'max_products' => 20, 'max_users' => 2],
        ])
        ->assertOk();

    expect($free->fresh()->slug)->toBe('free');
    expect($free->fresh()->features_json['max_tables'])->toBe(6);

    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    expect($tenant->fresh()->maxTablesAllowed())->toBe(6);
});

test('update com nome novo regenera o slug apenas em planos custom', function () {
    seedPlans();
    $custom = SaasPlan::create([
        'name' => 'Custom',
        'slug' => 'custom',
        'price_cents' => 7500,
        'interval' => 'month',
        'features_json' => ['max_tables' => 30],
        'is_active' => true,
    ]);

    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/plans/'.$custom->id, [
            'name' => 'Custom Novo',
        ])
        ->assertOk();

    expect($custom->fresh()->slug)->toBe('custom-novo');
});

test('update com nome novo mantém slug canônico dos planos de sistema', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();

    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/plans/'.$premium->id, [
            'name' => 'Premium Anual',
        ])
        ->assertOk();

    expect($premium->fresh()->slug)->toBe('premium');
});

test('tenant gratuito resolve plano com slug legado gratuito', function () {
    seedPlans();
    $free = SaasPlan::where('slug', 'free')->first();
    $free->update(['slug' => 'gratuito']);

    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);

    expect($tenant->currentPlan()?->slug)->toBe('free');
    expect($tenant->maxTablesAllowed())->toBe(2);
});

test('tenant pago com assinatura de plano custom usa os limites do plano da assinatura', function () {
    seedPlans();
    $custom = SaasPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_cents' => 5000,
        'interval' => 'month',
        'is_active' => true,
        'features_json' => ['max_tables' => 10, 'max_products' => 15, 'max_users' => 4],
    ]);
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $custom->id,
        'status' => 'active',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    expect($tenant->currentPlan()?->slug)->toBe('pro');
    expect($tenant->maxTablesAllowed())->toBe(10);
    expect($tenant->maxProductsAllowed())->toBe(15);
    expect($tenant->maxUsersAllowed())->toBe(4);
    expect($tenant->canAddTable())->toBeTrue();

    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/plans/'.$custom->id, [
            'features_json' => ['max_tables' => 8, 'max_products' => 15, 'max_users' => 4],
        ])
        ->assertOk();

    expect($tenant->fresh()->maxTablesAllowed())->toBe(8);
});

test('tenant pago sem assinatura ativa cai no premium', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    expect($tenant->currentPlan()?->slug)->toBe('premium');
    expect($tenant->maxTablesAllowed())->toBe(50);

    $tenant->update(['max_tables' => 12]);

    expect($tenant->maxTablesAllowed())->toBe(12);
});

test('superadmin muda tenant para plano custom criado no painel e os limites dele valem', function () {
    seedPlans();
    $teste = SaasPlan::create([
        'name' => 'Teste',
        'slug' => 'teste',
        'price_cents' => 15000,
        'interval' => 'month',
        'is_active' => true,
        'features_json' => ['max_tables' => 3, 'max_products' => 4, 'max_users' => 1],
    ]);
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);

    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/tenants/'.$tenant->id.'/plan', ['plan_id' => $teste->id])
        ->assertOk();

    $tenant->refresh();

    expect($tenant->plan)->toBe('paid');
    expect($tenant->currentPlan()?->slug)->toBe('teste');
    expect($tenant->maxTablesAllowed())->toBe(3);
    expect($tenant->maxProductsAllowed())->toBe(4);
    expect($tenant->maxUsersAllowed())->toBe(1);

    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/plans/'.$teste->id, [
            'features_json' => ['max_tables' => 9, 'max_products' => 4, 'max_users' => 1],
        ])
        ->assertOk();

    expect($tenant->fresh()->maxTablesAllowed())->toBe(9);
});

test('mensagem de limite: gratuito sugere upgrade, pago nao', function () {
    seedPlans();
    $free = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    $paid = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);

    expect($free->planLimitMessage('mesas', 3))->toBe('Seu plano permite apenas 3 mesas. Faça upgrade para Premium.');
    expect($paid->planLimitMessage('mesas', 3))->toBe('Seu plano permite apenas 3 mesas.');
    expect($paid->planLimitMessage('produtos', 5))->toBe('Seu plano permite apenas 5 produtos.');
});

test('produtos acima do limite somem da listagem do cardapio', function () {
    seedPlans();
    $premium = SaasPlan::where('slug', 'premium')->first();
    $this->actingAs(createSuperAdmin())
        ->putJson('/api/superadmin/plans/'.$premium->id, [
            'features_json' => ['max_tables' => 50, 'max_products' => 5, 'max_users' => 20],
        ])
        ->assertOk();

    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $user = createTenantAdmin($tenant);
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);
    $category2 = Category::factory()->create(['tenant_id' => $tenant->id]);
    $products = Product::factory()->count(5)->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);
    $hiddenProducts = Product::factory()->count(2)->create(['tenant_id' => $tenant->id, 'category_id' => $category2->id]);

    expect($tenant->hiddenProductsCount())->toBe(2);
    expect(count($tenant->manageableProductsIds()))->toBe(5);

    $component = Livewire::actingAs($user)->test(MenuManager::class);
    $html = $component->html();

    expect($html)->toContain('produtos ocultos');
    expect($html)->toContain('5 produto(s)');
    expect($html)->toContain('0 produto(s)');
    expect($html)->toContain('(+2 ocultos)');
    expect($html)->toContain($products[0]->name);
    expect($html)->toContain($products[4]->name);
    expect($html)->not->toContain($hiddenProducts[0]->name);
    expect($html)->not->toContain($hiddenProducts[1]->name);

    $component->call('switchView', 'products');
    expect($component->html())->not->toContain($hiddenProducts[0]->name);
    expect($component->html())->not->toContain($hiddenProducts[1]->name);
});

test('usuarios acima do limite somem da listagem de usuarios', function () {
    seedPlans();
    $tenant = createTenant(['plan' => Tenant::PLAN_FREE, 'status' => 'active']);
    $user = createTenantAdmin($tenant);
    $atendentes = User::factory()->count(3)->create(['tenant_id' => $tenant->id, 'role' => 'atendente']);

    expect($tenant->hiddenUsersCount())->toBe(2);

    $html = Livewire::actingAs($user)->test(UserManager::class)->html();

    expect($html)->toContain('usuários ocultos');
    expect($html)->toContain($atendentes[0]->name);
    expect($html)->not->toContain($atendentes[1]->name);
    expect($html)->not->toContain($atendentes[2]->name);
});

test('empresa criada no painel com plano custom permite criar ate o limite e bloqueia depois', function () {
    seedPlans();
    $teste = SaasPlan::create([
        'name' => 'Teste',
        'slug' => 'teste',
        'price_cents' => 15000,
        'interval' => 'month',
        'is_active' => true,
        'features_json' => ['max_tables' => 2, 'max_products' => 2, 'max_users' => 2],
    ]);

    $this->actingAs(createSuperAdmin())
        ->postJson('/api/superadmin/tenants', [
            'name' => 'Empresa Teste',
            'email' => 'empresa@teste.dev',
            'whatsapp' => null,
            'admin_name' => 'Admin Teste',
            'admin_password' => 'segredo123',
            'plan_id' => $teste->id,
        ])
        ->assertCreated()
        ->assertJsonPath('tenant.plan', 'paid');

    $tenant = Tenant::where('email', 'empresa@teste.dev')->first();
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);

    expect($tenant->currentPlan()?->slug)->toBe('teste');
    expect($tenant->maxTablesAllowed())->toBe(2);
    expect($tenant->maxProductsAllowed())->toBe(2);
    expect($tenant->maxUsersAllowed())->toBe(2);
    expect($tenant->canCreateProduct())->toBeTrue();
    expect($tenant->canCreateUser())->toBeTrue();

    Product::factory()->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);
    expect($tenant->fresh()->canCreateProduct())->toBeTrue();

    Product::factory()->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);
    expect($tenant->fresh()->canCreateProduct())->toBeFalse();
});

test('pagamento de plano custom ativa assinatura e atualiza limites e nome do plano', function () {
    seedPlans();
    $teste = SaasPlan::create([
        'name' => 'Plano Teste',
        'slug' => 'teste',
        'price_cents' => 15000,
        'interval' => 'month',
        'is_active' => true,
        'features_json' => ['max_tables' => 5, 'max_products' => 2, 'max_users' => 3],
    ]);

    $tenant = createTenant(['plan' => Tenant::PLAN_PAID, 'status' => 'active']);
    $admin = createTenantAdmin($tenant);

    $pending = SaasSubscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $teste->id,
        'status' => 'pending',
        'efi_charge_id' => 'txid-teste-1',
        'metadata' => ['months' => 1],
    ]);

    expect($tenant->currentPlan()?->slug)->not->toBe('teste');

    app(SaasEfiBankService::class)->processSaasWebhook([
        'txid' => 'txid-teste-1',
        'event' => 'payment_confirmed',
    ]);

    $tenant = $tenant->fresh();

    expect($pending->fresh()->status)->toBe('active');
    expect($tenant->currentPlan()?->slug)->toBe('teste');
    expect($tenant->currentPlan()?->name)->toBe('Plano Teste');
    expect($tenant->maxTablesAllowed())->toBe(5);
    expect($tenant->maxProductsAllowed())->toBe(2);
    expect($tenant->maxUsersAllowed())->toBe(3);
    expect($tenant->planLabel())->toBe('Plano Teste');
    expect($tenant->hiddenProductsCount())->toBe(0);
});
