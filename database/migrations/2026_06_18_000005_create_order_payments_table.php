<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->index()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('method', 20)->default('pix');
            $table->string('efi_charge_id')->nullable()->unique();
            $table->string('efi_pix_txid')->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->text('qrcode')->nullable();
            $table->text('qrcode_image')->nullable();
            $table->string('barcode')->nullable();
            $table->string('payment_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('webhook_received_at')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->json('efi_response_raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
