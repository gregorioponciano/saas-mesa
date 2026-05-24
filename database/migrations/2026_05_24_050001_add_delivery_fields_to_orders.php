<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_person_id')
                ->nullable()
                ->after('coupon_id')
                ->constrained('delivery_people')
                ->nullOnDelete();

            $table->decimal('delivery_cost', 10, 2)
                ->nullable()
                ->after('delivery_person_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_person_id');
            $table->dropColumn('delivery_cost');
        });
    }
};
