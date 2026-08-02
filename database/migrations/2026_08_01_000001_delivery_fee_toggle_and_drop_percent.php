<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'delivery_cost_percent')) {
                $table->dropColumn('delivery_cost_percent');
            }
            if (! Schema::hasColumn('tenants', 'delivery_cost_enabled')) {
                $table->boolean('delivery_cost_enabled')->default(true)->comment('taxa de entrega ativada/desativada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'delivery_cost_enabled')) {
                $table->dropColumn('delivery_cost_enabled');
            }
            if (! Schema::hasColumn('tenants', 'delivery_cost_percent')) {
                $table->decimal('delivery_cost_percent', 5, 2)->default(0);
            }
        });
    }
};
