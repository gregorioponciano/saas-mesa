<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('points_used')->default(false)->after('notes');
            $table->integer('points_spent')->default(0)->after('points_used');
            $table->decimal('points_discount', 10, 2)->default(0)->after('points_spent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_used', 'points_spent', 'points_discount']);
        });
    }
};
