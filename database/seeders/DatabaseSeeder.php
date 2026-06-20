<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SaasPlanSeeder::class,
            TenantSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductAttributeSeeder::class,
            TableSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
