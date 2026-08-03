<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SaasPlan;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        SaasPlan::updateOrCreate(['slug' => 'free'], [
            'name' => 'Gratuito',
            'price_cents' => 0,
            'interval' => 'month',
            'features_json' => [
                'max_tables' => 2,
                'max_products' => 20,
                'max_users' => 2,
                'pix_payments' => true,
                'boleto_payments' => false,
                'reports' => false,
                'delivery' => false,
                'priority_support' => false,
                'backup_retention_days' => 7,
                'backup_max_count' => 3,
            ],
            'feature_items' => [
                ['label' => 'Mesas máximas: 2', 'included' => true],
                ['label' => 'Produtos máximos: 20', 'included' => true],
                ['label' => 'Usuários máximos: 2', 'included' => true],
                ['label' => 'Pagamentos via PIX', 'included' => true],
            ],
            'is_active' => true,
        ]);

        SaasPlan::updateOrCreate(['slug' => 'premium'], [
            'name' => 'Premium',
            'price_cents' => 9790,
            'interval' => 'month',
            'features_json' => [
                'max_tables' => 20,
                'max_products' => 999,
                'max_users' => 20,
                'pix_payments' => true,
                'boleto_payments' => true,
                'reports' => true,
                'delivery' => true,
                'priority_support' => true,
                'backup_retention_days' => null,
                'backup_max_count' => 30,
            ],
            'feature_items' => [
                ['label' => 'Mesas máximas: 20', 'included' => true],
                ['label' => 'Produtos máximos: 999', 'included' => true],
                ['label' => 'Usuários máximos: 20', 'included' => true],
                ['label' => 'Pagamentos via PIX', 'included' => true],
                ['label' => 'Pedidos ilimitados', 'included' => true],
                ['label' => 'Delivery com entregadores', 'included' => true],
                ['label' => 'Relatórios avançados', 'included' => true],
                ['label' => 'Suporte prioritário', 'included' => true],
            ],
            'is_active' => true,
        ]);

    }
}
