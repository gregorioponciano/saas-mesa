<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIndexIfNotExists('orders', 'orders_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndexIfNotExists('orders', 'orders_tenant_id_type_status_index', ['tenant_id', 'type', 'status']);
        $this->createIndexIfNotExists('orders', 'orders_tenant_id_user_id_index', ['tenant_id', 'user_id']);
        $this->createIndexIfNotExists('orders', 'orders_tenant_id_created_at_index', ['tenant_id', 'created_at']);
        $this->createIndexIfNotExists('orders', 'orders_tenant_id_table_id_index', ['tenant_id', 'table_id']);
        $this->createIndexIfNotExists('orders', 'orders_tenant_id_payment_status_index', ['tenant_id', 'payment_status']);
        $this->createIndexIfNotExists('products', 'products_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndexIfNotExists('products', 'products_tenant_id_category_id_index', ['tenant_id', 'category_id']);
        $this->createIndexIfNotExists('tables', 'tables_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndexIfNotExists('payments', 'payments_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndexIfNotExists('payments', 'payments_order_id_status_index', ['order_id', 'status']);
        $this->createIndexIfNotExists('users', 'users_tenant_id_role_index', ['tenant_id', 'role']);
    }

    public function down(): void
    {
        Schema::table('orders', fn ($t) => $t->dropIndex('orders_tenant_id_payment_status_index'));
        Schema::table('orders', fn ($t) => $t->dropIndex('orders_tenant_id_table_id_index'));
        Schema::table('orders', fn ($t) => $t->dropIndex('orders_tenant_id_created_at_index'));
        Schema::table('orders', fn ($t) => $t->dropIndex('orders_tenant_id_user_id_index'));
        Schema::table('orders', fn ($t) => $t->dropIndex('orders_tenant_id_type_status_index'));
        Schema::table('orders', fn ($t) => $t->dropIndex('orders_tenant_id_status_index'));
        Schema::table('products', fn ($t) => $t->dropIndex('products_tenant_id_category_id_index'));
        Schema::table('products', fn ($t) => $t->dropIndex('products_tenant_id_status_index'));
        Schema::table('tables', fn ($t) => $t->dropIndex('tables_tenant_id_status_index'));
        Schema::table('payments', fn ($t) => $t->dropIndex('payments_order_id_status_index'));
        Schema::table('payments', fn ($t) => $t->dropIndex('payments_tenant_id_status_index'));
        Schema::table('users', fn ($t) => $t->dropIndex('users_tenant_id_role_index'));
    }

    private function createIndexIfNotExists(string $table, string $name, array $columns): void
    {
        if (!Schema::hasIndex($table, $name)) {
            Schema::table($table, fn ($t) => $t->index($columns, $name));
        }
    }
};
