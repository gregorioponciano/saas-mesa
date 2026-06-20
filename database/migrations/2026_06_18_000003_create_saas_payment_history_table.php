<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_payment_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->foreignId('tenant_id')->index()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('status', 20)->default('pending');
            $table->string('efi_charge_id')->nullable()->unique();
            $table->string('method', 20)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('saas_subscriptions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_payment_history');
    }
};
