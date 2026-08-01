<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('earned_at')->nullable();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['tenant_id', 'delivery_person_id', 'status']);
            $table->index(['tenant_id', 'delivery_person_id', 'earned_at']);
        });

        DB::table('delivery_earnings')->insertUsing(
            [
                'tenant_id', 'delivery_person_id', 'order_id', 'amount',
                'status', 'earned_at', 'created_at', 'updated_at',
            ],
            DB::table('orders')
                ->select(
                    'tenant_id',
                    'delivery_person_id',
                    'id as order_id',
                    'delivery_cost as amount',
                    DB::raw("'pending' as status"),
                    'delivered_at as earned_at',
                    DB::raw('CURRENT_TIMESTAMP as created_at'),
                    DB::raw('CURRENT_TIMESTAMP as updated_at')
                )
                ->whereNotNull('delivery_person_id')
                ->whereIn('status', ['entregue', 'fechado'])
                ->where('delivery_cost', '>', 0)
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_earnings');
    }
};
