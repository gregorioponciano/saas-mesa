<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'pix'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('pix', 'credit_card', 'debit_card', 'cash', 'other') NOT NULL DEFAULT 'pix'");
    }
};
