<?php

declare(strict_types=1);

use App\Livewire\Admin\CouponManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\LoyaltyManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\SubscriptionCheckout;
use App\Livewire\Admin\TableGrid;
use App\Livewire\Admin\TablesPage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('exibe o formulário de login do superadmin', function () {
    $this->get(route('superadmin.login'))->assertOk()->assertSee('Painel Superadmin');
});

it('não autentica usuário admin na rota de login do superadmin', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $this->post(route('superadmin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    expect(Auth::check())->toBeFalse();
});

it('autentica superadmin e redireciona ao painel', function () {
    $superadmin = createSuperAdmin();

    $this->post(route('superadmin.login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));

    expect(Auth::user()->role)->toBe('superadmin');
});

it('redireciona login geral de superadmin para o painel superadmin', function () {
    $superadmin = createSuperAdmin();

    $this->post(route('login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));
});

it('redireciona o superadmin logado de /login para o painel superadmin', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('login'))->assertRedirect(route('superadmin.dashboard'));
});

it('permite o superadmin entrar na conta de qualquer empresa com sessão ativa', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $companyAdmin = createTenantAdmin(createTenant());

    $this->post(route('login'), [
        'email' => $companyAdmin->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    expect(Auth::user()->role)->toBe('admin');
});

it('permite o superadmin entrar com a conta da própria empresa', function () {
    $tenant = createTenant();
    $companyAdmin = createTenantAdmin($tenant);
    $superadmin = User::factory()->create([
        'role' => 'superadmin',
        'tenant_id' => $tenant->id,
    ]);
    $this->actingAs($superadmin);

    $this->post(route('login'), [
        'email' => $companyAdmin->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    expect(Auth::user()->role)->toBe('admin');
});

it('permite o login da empresa depois que o superadmin desloga', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);
    $superadmin = createSuperAdmin();

    $this->actingAs($superadmin);
    $this->get(route('login'))->assertRedirect(route('superadmin.dashboard'));

    $this->post(route('superadmin.logout'))->assertRedirect(route('superadmin.login'));

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    expect(Auth::user()->role)->toBe('admin');
});

it('nega acesso ao painel superadmin para usuário comum', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);
    $this->actingAs($admin);

    $this->get(route('superadmin.dashboard'))->assertForbidden();
    $this->get(route('superadmin.tenants'))->assertForbidden();
});

it('redireciona visitante do painel para o login do superadmin', function () {
    $this->get(route('superadmin.dashboard'))->assertRedirect(route('superadmin.login'));
});

it('renderiza todas as páginas do painel superadmin autenticado', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.dashboard'))->assertOk()->assertSee('Visão Geral');
    $this->get(route('superadmin.tenants'))->assertOk()->assertSee('Empresas');
    $this->get(route('superadmin.plans'))->assertOk()->assertSee('Planos');
    $this->get(route('superadmin.financial'))->assertOk()->assertSee('Financeiro');
    $this->get(route('superadmin.loyalty'))->assertOk()->assertSee('Programa de Pontos');
});

it('acessa as APIs JSON do superadmin autenticado', function () {
    seedPlans();
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    createTenant(['plan' => Tenant::PLAN_PAID]);

    $this->getJson('/api/superadmin/financial/overview')->assertOk()->assertJsonPath('stats.active_tenants', 1);
    $this->getJson('/api/superadmin/tenants')->assertOk();
    $this->getJson('/api/superadmin/plans')->assertOk();
    $this->getJson('/api/superadmin/financial/payments')->assertOk();
});

it('faz logout do superadmin e limpa a sessão', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->post(route('superadmin.logout'))
        ->assertRedirect(route('superadmin.login'));

    expect(Auth::check())->toBeFalse();
});

it('mantém a sessão do navegador nas APIs JSON do painel (stateful)', function () {
    $superadmin = createSuperAdmin();

    $this->post(route('superadmin.login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));

    $this->withHeaders(['Referer' => route('superadmin.dashboard')])
        ->getJson('/api/superadmin/financial/overview')
        ->assertOk()
        ->assertJsonStructure(['stats' => ['active_tenants', 'mrr_cents']]);
});

it('nega acesso às APIs JSON sem sessão de navegador', function () {
    $this->withHeaders(['Referer' => route('superadmin.dashboard')])
        ->getJson('/api/superadmin/financial/overview')
        ->assertUnauthorized();
});

it('superadmin com sessão própria acessa o painel da empresa normalmente', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get('/dashboard')->assertOk();
});

it('bloqueia com 403 o superadmin no painel da empresa quando ele loga por cima de sessão de outra empresa', function () {
    $otherTenant = createTenant();
    $companyAdmin = createTenantAdmin($otherTenant);
    $superadmin = createSuperAdmin();

    $this->actingAs($companyAdmin);
    $this->post(route('superadmin.login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));

    $this->get('/dashboard')->assertForbidden();
    $this->get(route('dashboard.backup'))->assertForbidden();
    $this->get(route('dashboard.settings'))->assertForbidden();
    $this->get(route('dashboard.menu'))->assertForbidden();
});

it('libera o painel da empresa quando o superadmin loga por cima da própria empresa', function () {
    $tenant = createTenant();
    $companyAdmin = createTenantAdmin($tenant);
    $superadmin = User::factory()->create([
        'role' => 'superadmin',
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($companyAdmin);
    $this->post(route('superadmin.login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));

    $this->get('/dashboard')->assertOk();
});

it('após logout e novo login do superadmin sem empresa logada, o painel da empresa volta a abrir', function () {
    $otherTenant = createTenant();
    $companyAdmin = createTenantAdmin($otherTenant);
    $superadmin = createSuperAdmin();

    $this->actingAs($companyAdmin);
    $this->post(route('superadmin.login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));

    $this->get('/dashboard')->assertForbidden();

    $this->post(route('superadmin.logout'))->assertRedirect(route('superadmin.login'));

    $this->post(route('superadmin.login'), [
        'email' => $superadmin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));

    $this->get('/dashboard')->assertOk();
});

it('cria usuário superadmin via comando artisan', function () {
    $this->artisan('saas:create-superadmin', [
        '--name' => 'Super',
        '--email' => 'super@saas.test',
        '--password' => 'password123',
    ])->assertSuccessful();

    $user = User::where('email', 'super@saas.test')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('superadmin');
});

it('não quebra a página de cardápio quando superadmin navega até ela', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    Livewire::test(MenuManager::class)
        ->assertOk()
        ->assertSee('Nenhuma empresa vinculada')
        ->call('switchView', 'pontos')
        ->assertOk();
});

it('não quebra as demais páginas do painel da empresa com superadmin', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    Livewire::test(TablesPage::class)
        ->assertOk()
        ->assertSee('Nenhuma empresa vinculada');

    Livewire::test(LoyaltyManager::class)
        ->assertOk()
        ->assertSee('Nenhuma empresa vinculada');

    Livewire::test(CouponManager::class)
        ->assertOk()
        ->assertSee('Nenhuma empresa vinculada');

    Livewire::test(SubscriptionCheckout::class)
        ->assertOk()
        ->assertSee('Nenhuma empresa vinculada');

    Livewire::test(TableGrid::class)
        ->assertOk()
        ->assertSee('Nenhuma empresa vinculada');
});

it('bloqueia o mapa de mesas no dashboard sem empresa vinculada', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $dashboard = Livewire::test(Dashboard::class);
    $dashboard->call('switchTab', 'grid');
    expect($dashboard->get('tab'))->toBe('overview');
});
