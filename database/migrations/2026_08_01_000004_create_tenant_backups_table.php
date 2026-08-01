<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('filename');
            $table->string('disk', 50)->default('backups');
            $table->bigInteger('size_bytes')->default(0);
            $table->string('status', 20)->default('ready');
            $table->string('type', 20)->default('manual');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
    }
};
