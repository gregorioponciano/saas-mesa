<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('change_note');
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('cancelled_at');
                $table->boolean('is_points_item')->default(false)->after('cancelled_by');
                $table->unsignedInteger('points_cost')->nullable()->after('is_points_item');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'is_points_item', 'points_cost']);
        });
    }
};
