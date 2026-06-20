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
            if (!Schema::hasColumn('tenants', 'opening_time')) {
                $table->string('opening_time', 10)->nullable()->after('whatsapp');
                $table->string('closing_time', 10)->nullable()->after('opening_time');
                $table->decimal('delivery_cost_per_order', 10, 2)->default(0)->after('closing_time');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'type')) {
                $table->string('type', 20)->default('mesa')->after('status');
                $table->foreignId('table_id')->nullable()->after('type')->constrained()->nullOnDelete();
                $table->timestamp('bill_closed_at')->nullable()->after('notes');
                $table->foreignId('delivery_person_id')->nullable()->after('bill_closed_at')->constrained()->nullOnDelete();
                $table->decimal('delivery_cost', 10, 2)->default(0)->after('delivery_person_id');
                $table->decimal('payment_change', 10, 2)->default(0)->after('delivery_cost');
            }

            if (!Schema::hasColumn('orders', 'pronto')) {
                $table->enum('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue', 'cancelado', 'fechado'])
                    ->default('novo')
                    ->change();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'change_requested')) {
                $table->boolean('change_requested')->default(false)->after('selected_options_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', fn (Blueprint $t) => $t->dropColumn(['opening_time', 'closing_time', 'delivery_cost_per_order']));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['type', 'table_id', 'bill_closed_at', 'delivery_person_id', 'delivery_cost', 'payment_change']));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('change_requested'));
    }
};
