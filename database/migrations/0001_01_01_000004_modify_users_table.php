<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->nullable()->constrained()->nullOnDelete();
            $table->enum('role', ['admin', 'atendente'])->after('password')->default('admin');
            $table->json('passkey_credentials')->after('role')->nullable();
            $table->dropUnique('users_email_unique');
            $table->unique(['email', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'role', 'passkey_credentials']);
            $table->dropUnique('users_email_tenant_id_unique');
            $table->unique('email');
        });
    }
};
