<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;

test('cardapio publico e acessivel', function () {
    $tenant = Tenant::factory()->create(['slug' => 'test-lanchonete']);

    $response = $this->get('/cardapio/test-lanchonete');
    $response->assertStatus(200);
});

test('cardapio retorna 404 para slug invalido', function () {
    $response = $this->get('/cardapio/slug-inexistente');
    $response->assertStatus(404);
});

test('cardapio exibe produtos ativos', function () {
    $tenant = Tenant::factory()->create();
    $category = Category::factory()->create(['tenant_id' => $tenant->id]);
    $activeProduct = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);
    $inactiveProduct = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'status' => 'inactive',
    ]);

    $response = $this->get("/cardapio/{$tenant->slug}");

    $response->assertStatus(200);
    $response->assertSee($activeProduct->name);
    $response->assertDontSee($inactiveProduct->name);
});
