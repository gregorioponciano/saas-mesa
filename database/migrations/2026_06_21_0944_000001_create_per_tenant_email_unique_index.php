<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('users', 'users_email_unique')) {
            Schema::table('users', function ($table) {
                $table->dropUnique('users_email_unique');
            });
        }

        if (!Schema::hasIndex('users', 'users_email_tenant_id_unique')) {
            Schema::table('users', function ($table) {
                $table->unique(['tenant_id', 'email']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', 'users_email_tenant_id_unique')) {
            Schema::table('users', function ($table) {
                $table->dropUnique('users_email_tenant_id_unique');
            });
        }

        if (!Schema::hasIndex('users', 'users_email_unique')) {
            Schema::table('users', function ($table) {
                $table->unique(['email']);
            });
        }
    }
};
