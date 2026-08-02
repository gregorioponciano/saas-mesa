<?php

declare(strict_types=1);

use App\Livewire\Admin\SidebarCounts;
use App\Models\Category;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\Table;
use Livewire\Livewire;

it('calcula contagens da sidebar para plano free', function () {
    $tenant = createTenant(['plan' => 'free', 'max_tables' => 2]);
    $admin = createTenantAdmin($tenant);

    $category = Category::factory()->create(['tenant_id' => $tenant->id]);
    Product::factory()->count(3)->create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'status' => 'active']);
    Product::factory()->count(1)->create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'status' => 'inactive']);

    Table::factory()->create(['tenant_id' => $tenant->id, 'number' => '1', 'status' => 'free']);
    Table::factory()->create(['tenant_id' => $tenant->id, 'number' => '2', 'status' => 'occupied']);
    Table::factory()->create(['tenant_id' => $tenant->id, 'number' => '3', 'status' => 'free']);
    Table::factory()->create(['tenant_id' => $tenant->id, 'number' => '4', 'status' => 'occupied']);

    SupportTicket::create(['tenant_id' => $tenant->id, 'status' => 'aberto', 'subject' => 'Teste', 'user_id' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(SidebarCounts::class);

    $component = Livewire::actingAs($admin)->test(SidebarCounts::class)->instance();

    expect($component->tablesCount)->toBe(2)
        ->and($component->occupiedTablesCount)->toBe(1)
        ->and($component->activeProductsCount)->toBe(3)
        ->and($component->disabledProductsCount)->toBe(1)
        ->and($component->openTicketsCount)->toBe(1);
});

it('zera contagens quando não há dados', function () {
    $tenant = createTenant(['plan' => 'free', 'max_tables' => 2]);
    $admin = createTenantAdmin($tenant);

    $component = Livewire::actingAs($admin)->test(SidebarCounts::class)->instance();

    expect($component->tablesCount)->toBe(0)
        ->and($component->occupiedTablesCount)->toBe(0)
        ->and($component->activeProductsCount)->toBe(0)
        ->and($component->disabledProductsCount)->toBe(0)
        ->and($component->openTicketsCount)->toBe(0);
});
