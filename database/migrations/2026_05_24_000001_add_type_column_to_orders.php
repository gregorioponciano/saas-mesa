<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('type', 20)->default('mesa')->after('status');
            });
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'novo'");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('novo', 'em_preparo', 'saiu_entrega', 'entregue', 'cancelado') NOT NULL DEFAULT 'novo'");
    }
};
