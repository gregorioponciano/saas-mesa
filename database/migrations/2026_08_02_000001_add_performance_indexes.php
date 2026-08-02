<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_invoices', function (Blueprint $table) {
            if (! Schema::hasIndex('tenant_invoices', 'tenant_invoices_tenant_id_created_at_index')) {
                $table->index(['tenant_id', 'created_at']);
            }
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            if (! Schema::hasIndex('webhook_logs', 'webhook_logs_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('saas_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_plans', 'border_color')) {
                $table->string('border_color', 20)->nullable()->after('features_json');
            }
            if (! Schema::hasColumn('saas_plans', 'background_color')) {
                $table->string('background_color', 20)->nullable()->after('border_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_invoices', function (Blueprint $table) {
            $table->dropIndex('tenant_invoices_tenant_id_created_at_index');
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropIndex('webhook_logs_created_at_index');
        });

        Schema::table('saas_plans', function (Blueprint $table) {
            $table->dropColumn(['border_color', 'background_color']);
        });
    }
};
