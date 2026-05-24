<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('bill_closed_at')->nullable()->after('notes');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('change_requested')->default(false)->after('selected_options_json');
            $table->timestamp('change_requested_at')->nullable()->after('change_requested');
            $table->text('change_note')->nullable()->after('change_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('bill_closed_at');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['change_requested', 'change_requested_at', 'change_note']);
        });
    }
};
