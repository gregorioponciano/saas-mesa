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
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
            $table->string('payment_status', 20)->default('pending')->after('discount_type');
            $table->string('efi_charge_id')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('efi_charge_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
            $table->string('efi_charge_id')->nullable()->after('payment_method');
            $table->string('efi_pix_txid')->nullable()->after('efi_charge_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        Schema::table('delivery_people', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', fn (Blueprint $t) => $t->dropColumn('uuid'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['uuid', 'payment_status', 'efi_charge_id', 'paid_at']));
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn(['uuid', 'efi_charge_id', 'efi_pix_txid']));
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('uuid'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('uuid'));
        Schema::table('tables', fn (Blueprint $t) => $t->dropColumn('uuid'));
        Schema::table('coupons', fn (Blueprint $t) => $t->dropColumn('uuid'));
        Schema::table('delivery_people', fn (Blueprint $t) => $t->dropColumn('uuid'));
        Schema::table('ingredients', fn (Blueprint $t) => $t->dropColumn('uuid'));
    }
};
