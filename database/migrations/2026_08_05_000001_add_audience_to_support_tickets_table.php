<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // 'tenant' = cliente fala com a empresa (comportamento atual, default);
            // 'platform' = empresa fala com a plataforma (superadmin).
            $table->enum('audience', ['tenant', 'platform'])->default('tenant')->after('status');
            $table->index(['audience', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['audience', 'status']);
            $table->dropColumn('audience');
        });
    }
};
