<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_bonificacao')->default(false)->after('is_points_item');
            $table->string('bonificacao_reason')->nullable()->after('is_bonificacao');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['is_bonificacao', 'bonificacao_reason']);
        });
    }
};
