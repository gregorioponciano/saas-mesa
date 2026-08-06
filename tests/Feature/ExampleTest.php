<?php

declare(strict_types=1);

it('a home pública responde com sucesso para visitantes', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('a home pública exibe os planos do banco de dados', function () {
    seedPlans();
    \App\Models\SaasPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_cents' => 15000,
        'interval' => 'month',
        'features_json' => ['max_tables' => 100, 'max_products' => 500, 'max_users' => 100],
        'feature_items' => [
            ['label' => 'Mesas máximas: 100', 'included' => true],
            ['label' => 'Suporte Pro', 'included' => false],
        ],
        'is_active' => true,
    ]);

    $this->get('/')
        ->assertStatus(200)
        ->assertSee('Gratuito')
        ->assertSee('Premium')
        ->assertSee('Mesas máximas: 2')
        ->assertSee('Produtos máximos: 999')
        ->assertSee('Pro')
        ->assertSee('Assinar Pro')
        ->assertSee('R$ <span class="text-3xl">150</span>', false);
});

it('o menu público de um tenant responde com sucesso', function () {
    $tenant = createTenant();

    $this->get("/cardapio/{$tenant->slug}")
        ->assertStatus(200)
        ->assertSee($tenant->name);
});

it('a página de acesso do garçom responde com sucesso', function () {
    $tenant = createTenant();

    $this->get("/cardapio/{$tenant->slug}/acesso")
        ->assertStatus(200);
});

it('a página de termos de uso responde com sucesso', function () {
    $this->get('/termos-de-uso')
        ->assertStatus(200)
        ->assertSee('Termos de Uso');
});

it('a página de política de privacidade responde com sucesso', function () {
    $this->get('/politica-de-privacidade')
        ->assertStatus(200);
});

it('o painel exige autenticação', function () {
    $this->get('/dashboard')
        ->assertRedirect(route('login'));
});
