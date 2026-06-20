<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_billing_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('billing_type', 30)->default('fixed');
            $table->unsignedBigInteger('monthly_fee_cents')->default(0);
            $table->unsignedBigInteger('per_transaction_fee_cents')->default(0);
            $table->unsignedTinyInteger('billing_day')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_billing_configs');
    }
};
