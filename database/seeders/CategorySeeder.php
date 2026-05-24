<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'classic-burger-artisan')->firstOrFail();

        $categories = [
            ['name' => 'Hambúrgueres Artesanais', 'slug' => 'hamburgueres', 'position' => 1],
            ['name' => 'Acompanhamentos', 'slug' => 'acompanhamentos', 'position' => 2],
            ['name' => 'Bebidas', 'slug' => 'bebidas', 'position' => 3],
            ['name' => 'Sobremesas', 'slug' => 'sobremesas', 'position' => 4],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug'], 'tenant_id' => $tenant->id],
                array_merge($category, ['tenant_id' => $tenant->id])
            );
        }
    }
}
