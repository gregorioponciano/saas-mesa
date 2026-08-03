<?php

namespace Database\Seeders;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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

        // Garante uma assinatura ativa do plano Premium apontada pelo tenant,
        // para que a página de assinatura mostre o plano atual ("Atual"/"Premium").
        $premium = SaasPlan::where('slug', 'premium')->first();

        if ($premium && empty($tenant->subscription_id)) {
            $subscription = SaasSubscription::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'plan_id' => $premium->id,
                ],
                [
                    'status' => 'active',
                    'payment_method' => 'pix',
                    'current_period_start' => Carbon::now()->startOfMonth(),
                    'current_period_end' => Carbon::now()->endOfMonth(),
                    'next_billing_date' => Carbon::now()->addMonth()->startOfMonth(),
                ]
            );

            $tenant->update([
                'subscription_id' => $subscription->id,
                'subscription_ends_at' => $subscription->current_period_end,
            ]);
        }

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
