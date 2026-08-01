<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','atendente','cliente','superadmin') NOT NULL DEFAULT 'cliente'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','atendente','cliente') NOT NULL DEFAULT 'cliente'");
    }
};
