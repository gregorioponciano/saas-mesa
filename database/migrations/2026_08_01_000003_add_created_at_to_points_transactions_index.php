<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('points_transactions', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id', 'created_at'], 'points_transactions_tenant_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('points_transactions', function (Blueprint $table) {
            $table->dropIndex('points_transactions_tenant_user_created_idx');
        });
    }
};
