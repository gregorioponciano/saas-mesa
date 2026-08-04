<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Versões antigas do SaasPlanSeeder criavam o plano gratuito com slug
     * 'gratuito'. O código resolve o plano do tenant pelo slug 'free'
     * (Tenant::currentPlan). Renomeia o slug legado apenas se 'free' ainda
     * não existir, preservando as features editadas pelo superadmin.
     */
    public function up(): void
    {
        if (! DB::table('saas_plans')->where('slug', 'free')->exists()) {
            DB::table('saas_plans')->where('slug', 'gratuito')->update(['slug' => 'free']);
        }
    }

    public function down(): void
    {
        if (! DB::table('saas_plans')->where('slug', 'gratuito')->exists()) {
            DB::table('saas_plans')->where('slug', 'free')->update(['slug' => 'gratuito']);
        }
    }
};
