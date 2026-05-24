<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'novo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('novo', 'em_preparo', 'saiu_entrega', 'entregue', 'cancelado') NOT NULL DEFAULT 'novo'");
    }
};
