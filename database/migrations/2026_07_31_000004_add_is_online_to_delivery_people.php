<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_people', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_people', 'is_online')) {
                $table->boolean('is_online')->default(false);
            }
        });

        DB::table('delivery_people')->where('status', 'active')->update(['is_online' => true]);
    }

    public function down(): void
    {
        Schema::table('delivery_people', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_people', 'is_online')) {
                $table->dropColumn('is_online');
            }
        });
    }
};
