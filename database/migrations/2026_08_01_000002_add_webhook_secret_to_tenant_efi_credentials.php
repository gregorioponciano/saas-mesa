<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_efi_credentials', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_efi_credentials', 'webhook_secret_encrypted')) {
                $table->text('webhook_secret_encrypted')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_efi_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_efi_credentials', 'webhook_secret_encrypted')) {
                $table->dropColumn('webhook_secret_encrypted');
            }
        });
    }
};
