<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'cancelled_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            });
        }

        DB::table('orders')
            ->where('status', 'cancelado')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};
