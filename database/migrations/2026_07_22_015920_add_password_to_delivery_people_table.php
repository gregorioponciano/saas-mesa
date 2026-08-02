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
        Schema::table('delivery_people', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_people', 'password')) {
                $table->string('password')->nullable()->after('api_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_people', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_people', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};
