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
            ],
            'is_active' => true,
        ]);

        SaasPlan::updateOrCreate(['slug' => 'premium'], [
            'name' => 'Premium',
            'price_cents' => 9790,
            'interval' => 'month',
            'features_json' => [
                'max_tables' => 50,
                'max_products' => 999,
                'max_users' => 20,
                'pix_payments' => true,
                'boleto_payments' => true,
                'reports' => true,
                'delivery' => true,
                'priority_support' => true,
            ],
            'is_active' => true,
        ]);


    }
}
