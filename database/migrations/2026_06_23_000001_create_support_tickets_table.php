<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 200);
            $table->enum('category', ['pedido', 'pagamento', 'cardapio', 'entrega', 'conta', 'outro'])->default('outro');
            $table->enum('priority', ['baixa', 'media', 'alta'])->default('media');
            $table->enum('status', ['aberto', 'em_atendimento', 'aguardando_cliente', 'resolvido', 'fechado'])->default('aberto');
            $table->string('order_id')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
