<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->string('author_role', 30)->nullable();
            $table->string('author_name', 100)->nullable();
            $table->timestamps();
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
