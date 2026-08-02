<?php

declare(strict_types=1);

it('a home pública responde com sucesso para visitantes', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
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

it('o painel exige autenticação', function () {
    $this->get('/dashboard')
        ->assertRedirect(route('login'));
});
