<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'classic-burger-artisan')->firstOrFail();

        $smashBurger = Product::where('name', 'Smash Burger Duplo')->where('tenant_id', $tenant->id)->firstOrFail();

        $pontoCarne = ProductAttribute::firstOrCreate(
            ['product_id' => $smashBurger->id, 'name' => 'Ponto da carne'],
            [
                'tenant_id' => $tenant->id,
                'type' => 'single',
                'is_required' => true,
                'position' => 1,
            ]
        );

        $options = [
            ['name' => 'Mal passado', 'price_additional' => 0, 'position' => 1],
            ['name' => 'Ao ponto', 'price_additional' => 0, 'position' => 2],
            ['name' => 'Bem passado', 'price_additional' => 0, 'position' => 3],
        ];

        foreach ($options as $option) {
            ProductAttributeOption::firstOrCreate(
                ['product_attribute_id' => $pontoCarne->id, 'name' => $option['name']],
                $option
            );
        }

        $adicionais = ProductAttribute::firstOrCreate(
            ['product_id' => $smashBurger->id, 'name' => 'Adicionais'],
            [
                'tenant_id' => $tenant->id,
                'type' => 'multiple',
                'is_required' => false,
                'position' => 2,
            ]
        );

        $smashOptions = [
            ['name' => 'Bacon Crocante', 'price_additional' => 4.00, 'position' => 1],
            ['name' => 'Cebola Caramelizada', 'price_additional' => 3.00, 'position' => 2],
            ['name' => 'Mudar para Queijo Prato', 'price_additional' => 0.00, 'position' => 3],
            ['name' => 'Ovo', 'price_additional' => 2.00, 'position' => 4],
        ];

        foreach ($smashOptions as $option) {
            ProductAttributeOption::firstOrCreate(
                ['product_attribute_id' => $adicionais->id, 'name' => $option['name']],
                $option
            );
        }

        $classicBurger = Product::where('name', 'Classic Burger')->where('tenant_id', $tenant->id)->firstOrFail();

        $classicAdicionais = ProductAttribute::firstOrCreate(
            ['product_id' => $classicBurger->id, 'name' => 'Adicionais'],
            [
                'tenant_id' => $tenant->id,
                'type' => 'multiple',
                'is_required' => false,
                'position' => 1,
            ]
        );

        $classicOptions = [
            ['name' => 'Bacon Extra', 'price_additional' => 4.50, 'position' => 1],
            ['name' => 'Cheddar Extra', 'price_additional' => 3.00, 'position' => 2],
            ['name' => 'Molho Barbecue', 'price_additional' => 1.50, 'position' => 3],
        ];

        foreach ($classicOptions as $option) {
            ProductAttributeOption::firstOrCreate(
                ['product_attribute_id' => $classicAdicionais->id, 'name' => $option['name']],
                $option
            );
        }
    }
}
