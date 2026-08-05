<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'change_note')) {
                $table->text('change_note')->nullable();
            }
            if (! Schema::hasColumn('order_items', 'change_requested_at')) {
                $table->timestamp('change_requested_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'change_note')) {
                $table->dropColumn('change_note');
            }
            if (Schema::hasColumn('order_items', 'change_requested_at')) {
                $table->dropColumn('change_requested_at');
            }
        });
    }
};
