<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add tenant_id to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $table->string('role', 30)->default('cliente')->after('password');
                $table->boolean('is_staff')->default(false)->after('role');
                $table->text('passkey_credentials')->nullable()->after('is_staff');
            }
        });

        // Add missing columns to orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone', 20)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('orders', 'payment_change')) {
                $table->decimal('payment_change', 10, 2)->default(0)->after('delivery_cost');
            }
        });

        // Add delivery_cost to tenants
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'delivery_cost_per_order')) {
                $table->decimal('delivery_cost_per_order', 10, 2)->default(0)->after('subscription_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['role', 'is_staff', 'passkey_credentials']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_phone', 'payment_change']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('delivery_cost_per_order');
        });
    }
};
