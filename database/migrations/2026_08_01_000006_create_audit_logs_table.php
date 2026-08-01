<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('action', 80);
            $table->string('entity_type', 80)->nullable();
            $table->string('entity_id', 80)->nullable();
            $table->string('description')->nullable();
            $table->json('data')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
