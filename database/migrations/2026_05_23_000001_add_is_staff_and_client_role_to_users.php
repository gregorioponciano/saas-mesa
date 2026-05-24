<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_staff')->after('role')->default(false);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'atendente', 'cliente') NOT NULL DEFAULT 'cliente'");

        DB::table('users')->where('role', 'admin')->update(['is_staff' => true]);
        DB::table('users')->where('role', 'atendente')->update(['is_staff' => true]);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'cliente')->update(['role' => 'atendente']);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'atendente') NOT NULL DEFAULT 'admin'");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_staff');
        });
    }
};
