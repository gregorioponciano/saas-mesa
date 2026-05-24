<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('slug')->unique();
            $table->string('domain')->unique()->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('plan', 20)->default('free');
            $table->unsignedSmallInteger('max_tables')->default(2);
            $table->string('status', 20)->default('trial');
            $table->string('subscription_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
