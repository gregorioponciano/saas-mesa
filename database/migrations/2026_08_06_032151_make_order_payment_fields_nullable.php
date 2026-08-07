<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_change')) {
                $table->decimal('payment_change', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('orders', 'delivery_cost')) {
                $table->decimal('delivery_cost', 10, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_change')) {
                $table->decimal('payment_change', 10, 2)->default(0)->change();
            }
            if (Schema::hasColumn('orders', 'delivery_cost')) {
                $table->decimal('delivery_cost', 10, 2)->default(0)->change();
            }
        });
    }
};
