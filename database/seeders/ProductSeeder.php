<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'classic-burger-artisan')->firstOrFail();

        $hamburgueres = Category::where('slug', 'hamburgueres')->where('tenant_id', $tenant->id)->firstOrFail();
        $acompanhamentos = Category::where('slug', 'acompanhamentos')->where('tenant_id', $tenant->id)->firstOrFail();
        $bebidas = Category::where('slug', 'bebidas')->where('tenant_id', $tenant->id)->firstOrFail();
        $sobremesas = Category::where('slug', 'sobremesas')->where('tenant_id', $tenant->id)->firstOrFail();

        $products = [
            // Hambúrgueres
            [
                'category_id' => $hamburgueres->id,
                'name' => 'Smash Burger Duplo',
                'description' => 'Pão brioche artesanal, 2 blends de 90g, queijo cheddar maçaricado, maionese artesanal da casa, alface americana e picles.',
                'price' => 28.90,
                'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $hamburgueres->id,
                'name' => 'Classic Burger',
                'description' => 'Pão australiano, blend 180g, queijo prato, cebola roxa, tomate, alface e molho especial.',
                'price' => 24.90,
                'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $hamburgueres->id,
                'name' => 'Barbecue Bacon',
                'description' => 'Pão brioche, blend 180g, barbecue artesanal, bacon crocante, cebola caramelizada e queijo cheddar.',
                'price' => 32.90,
                'image_url' => 'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $hamburgueres->id,
                'name' => 'Veggie Burger',
                'description' => 'Pão integral, hambúrguer de grão-de-bico, homus, rúcula, tomate seco e cebola roxa.',
                'price' => 26.90,
                'image_url' => 'https://images.unsplash.com/photo-1585238342024-78d387f4a707?w=500&q=80',
                'status' => 'active',
            ],
            // Acompanhamentos
            [
                'category_id' => $acompanhamentos->id,
                'name' => 'Batata Rústica com Alecrim',
                'description' => 'Batatas rústicas temperadas com alecrim, sal marinho e páprica defumada. Serve 2 pessoas.',
                'price' => 14.90,
                'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $acompanhamentos->id,
                'name' => 'Anéis de Cebola Empanados',
                'description' => 'Anéis de cebola empanados na hora, servidos com molho ranch.',
                'price' => 12.90,
                'image_url' => 'https://images.unsplash.com/photo-1639024471283-03518883512d?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $acompanhamentos->id,
                'name' => 'Polenta Frita com Queijo',
                'description' => 'Porção de polenta frita crocante coberta com queijo parmesão. Serve 2 pessoas.',
                'price' => 13.90,
                'image_url' => 'https://images.unsplash.com/photo-1724681857307-f6938a0562eb?w=500&q=80',
                'status' => 'active',
            ],
            // Bebidas
            [
                'category_id' => $bebidas->id,
                'name' => 'Coca-Cola Lata 350ml',
                'description' => 'Refrigerante Coca-Cola original em lata.',
                'price' => 6.00,
                'image_url' => 'https://images.unsplash.com/photo-1629203851122-3726ecdfcb80?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $bebidas->id,
                'name' => 'Guaraná Antarctica Lata 350ml',
                'description' => 'Refrigerante Guaraná Antarctica em lata.',
                'price' => 5.00,
                'image_url' => 'https://images.unsplash.com/photo-1629203851122-3726ecdfcb80?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $bebidas->id,
                'name' => 'Suco Natural de Laranja 500ml',
                'description' => 'Suco de laranja natural espremido na hora.',
                'price' => 8.90,
                'image_url' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $bebidas->id,
                'name' => 'Água Mineral 500ml',
                'description' => 'Água mineral sem gás.',
                'price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1564419320468-68791a0e2190?w=500&q=80',
                'status' => 'active',
            ],
            // Sobremesas
            [
                'category_id' => $sobremesas->id,
                'name' => 'Milkshake de Chocolate',
                'description' => 'Milkshake cremoso de chocolate com calda e chantilly.',
                'price' => 16.90,
                'image_url' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=500&q=80',
                'status' => 'active',
            ],
            [
                'category_id' => $sobremesas->id,
                'name' => 'Petit Gâteau',
                'description' => 'Bolinho de chocolate com recheio cremoso, servido com sorvete de creme.',
                'price' => 18.90,
                'image_url' => 'https://images.unsplash.com/photo-1624353365286-3f8d62daad51?w=500&q=80',
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            $data = array_merge($product, ['tenant_id' => $tenant->id]);
            Product::firstOrCreate(
                ['name' => $product['name'], 'tenant_id' => $tenant->id],
                $data
            );
        }

        Product::where('tenant_id', $tenant->id)->update(['stock' => 10]);
    }
}
