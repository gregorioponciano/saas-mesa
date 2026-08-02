<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'delivery_cost_type')) {
                $table->string('delivery_cost_type', 10)->default('fixed')->comment('fixed|per_km');
            }
            if (! Schema::hasColumn('tenants', 'delivery_cost_per_km')) {
                $table->decimal('delivery_cost_per_km', 10, 2)->default(0)->comment('R$ por km');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'delivery_cost_type')) {
                $table->dropColumn('delivery_cost_type');
            }
            if (Schema::hasColumn('tenants', 'delivery_cost_per_km')) {
                $table->dropColumn('delivery_cost_per_km');
            }
        });
    }
};
