<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = DB::select('SHOW INDEX FROM users');
        $indexNames = array_column($indexes, 'Key_name');

        Schema::table('users', function ($table) use ($indexNames) {
            if (in_array('users_email_unique', $indexNames, true)) {
                $table->dropUnique(['email']);
            }

            if (!in_array('users_email_tenant_id_unique', $indexNames, true)) {
                $table->unique(['tenant_id', 'email']);
            }
        });
    }

    public function down(): void
    {
        $indexes = DB::select('SHOW INDEX FROM users');
        $indexNames = array_column($indexes, 'Key_name');

        Schema::table('users', function ($table) use ($indexNames) {
            if (in_array('users_email_tenant_id_unique', $indexNames, true)) {
                $table->dropUnique(['tenant_id', 'email']);
            }

            if (!in_array('users_email_unique', $indexNames, true)) {
                $table->string('email')->unique()->change();
            }
        });
    }
};
