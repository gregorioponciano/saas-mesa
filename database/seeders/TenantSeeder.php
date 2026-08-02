<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'classic-burger-artisan'],
            [
                'name' => 'Classic Burger Artisan',
                'email' => 'admin@classicburger.com',
                'opening_time' => '00:00:00',
                'closing_time' => '23:59:00',
                'plan' => Tenant::PLAN_PAID,
                'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_PAID],
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'contato@classicburger.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'gregorio@saasmesa.com.br'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => 'saasmesa123',
                'role' => 'superadmin',
            ]
        );
    }
}
