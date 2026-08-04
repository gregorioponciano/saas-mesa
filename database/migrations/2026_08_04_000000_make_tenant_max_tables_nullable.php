<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tenants.max_tables vira um override opcional por tenant (contratos
     * especiais). NULL = usar o limite definido no plano ativo
     * (SaasPlan.features_json), que o superadmin pode alterar em runtime.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_tables')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_tables')->default(2)->change();
        });
    }
};
