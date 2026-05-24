<?php

use App\Models\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add token to tables for existing databases
        if (!Schema::hasColumn('tables', 'token')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->uuid('token')->nullable()->after('id');
            });

            foreach (Table::all() as $table) {
                $table->token = (string) Str::uuid();
                $table->save();
            }

            Schema::table('tables', function (Blueprint $table) {
                $table->uuid('token')->nullable(false)->change();
                $table->unique('token');
            });
        }

        // Remove name from tables for existing databases
        if (Schema::hasColumn('tables', 'name')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        // Add email to tenants for existing databases
        if (!Schema::hasColumn('tenants', 'email')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('email')->unique()->after('name');
            });
        }

        // Drop legacy tables if they still exist
        Schema::dropIfExists('table_user');
        Schema::dropIfExists('table_empresas');
    }

    public function down(): void
    {
        // No rollback — this is a consolidation, not a feature
    }
};
