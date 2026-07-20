<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_loyalty_configs', function (Blueprint $table) {
            $table->decimal('min_points_order_value', 10, 2)->default(10.00)->after('points_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_loyalty_configs', function (Blueprint $table) {
            $table->dropColumn('min_points_order_value');
        });
    }
};
