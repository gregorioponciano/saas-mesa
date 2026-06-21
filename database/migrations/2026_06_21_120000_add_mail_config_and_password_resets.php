<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'mail_host')) {
                $table->string('mail_host')->nullable()->after('delivery_cost_per_order');
                $table->string('mail_port')->nullable()->after('mail_host');
                $table->string('mail_username')->nullable()->after('mail_port');
                $table->text('mail_password')->nullable()->after('mail_username');
                $table->string('mail_encryption')->nullable()->after('mail_password');
                $table->string('mail_from_address')->nullable()->after('mail_encryption');
                $table->string('mail_from_name')->nullable()->after('mail_from_address');
            }
        });

        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
                $table->unique(['email', 'tenant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'mail_host', 'mail_port', 'mail_username', 'mail_password',
                'mail_encryption', 'mail_from_address', 'mail_from_name',
            ]);
        });

        Schema::dropIfExists('password_resets');
    }
};
