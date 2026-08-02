<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->alterUsers();
        $this->alterTenants();
        $this->alterOrders();
        $this->alterOrderItems();
        $this->alterDeliveryPeople();
        $this->alterProducts();
        $this->alterProductAttributes();
        $this->alterProductAttributeOptions();
        $this->alterTables();
        $this->alterPayments();
        $this->alterCoupons();
        $this->alterCategories();
        $this->alterIngredients();
        $this->alterLoyaltyConfigs();
        $this->createStockMovements();
        $this->createPasswordResets();
        $this->createIndexes();
    }

    private function alterUsers(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('role', 30)->default('cliente');
                $table->boolean('is_staff')->default(false);
                $table->text('passkey_credentials')->nullable();
            }
        });

        if (Schema::hasIndex('users', 'users_email_unique')) {
            Schema::table('users', fn ($t) => $t->dropUnique('users_email_unique'));
        }
        if (! Schema::hasIndex('users', 'users_tenant_id_email_unique')) {
            Schema::table('users', fn ($t) => $t->unique(['tenant_id', 'email'], 'users_tenant_id_email_unique'));
        }
    }

    private function alterTenants(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'logo')) {
                $table->string('logo')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'logo_width')) {
                $table->unsignedSmallInteger('logo_width')->default(44);
                $table->unsignedSmallInteger('logo_height')->default(44);
            }
            if (! Schema::hasColumn('tenants', 'opening_time')) {
                $table->string('opening_time', 10)->nullable();
                $table->string('closing_time', 10)->nullable();
                $table->decimal('delivery_cost_per_order', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('tenants', 'mail_host')) {
                $table->string('mail_host')->nullable();
                $table->string('mail_port')->nullable();
                $table->string('mail_username')->nullable();
                $table->text('mail_password')->nullable();
                $table->string('mail_encryption')->nullable();
                $table->string('mail_from_address')->nullable();
                $table->string('mail_from_name')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'coupons_enabled')) {
                $table->boolean('coupons_enabled')->default(true);
            }
            if (! Schema::hasColumn('tenants', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
        });
    }

    private function alterOrders(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
            if (! Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone', 20)->nullable();
            }
            if (! Schema::hasColumn('orders', 'type')) {
                $table->string('type', 20)->default('mesa');
                $table->foreignId('table_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('bill_closed_at')->nullable();
                $table->foreignId('delivery_person_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('delivery_cost', 10, 2)->default(0);
                $table->decimal('payment_change', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 20)->default('pending');
                $table->string('efi_charge_id')->nullable();
                $table->timestamp('paid_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'points_used')) {
                $table->boolean('points_used')->default(false);
                $table->integer('points_spent')->default(0);
                $table->decimal('points_discount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'accepted_at')) {
                $table->enum('status', [
                    'novo', 'em_preparo', 'pronto', 'coletado',
                    'saiu_entrega', 'entregue', 'cancelado', 'fechado',
                ])->default('novo')->change();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('picked_up_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->string('delivery_photo_path')->nullable();
                $table->decimal('delivery_lat', 10, 7)->nullable();
                $table->decimal('delivery_lng', 10, 7)->nullable();
            }
        });
    }

    private function alterOrderItems(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'change_requested')) {
                $table->boolean('change_requested')->default(false);
            }
            if (! Schema::hasColumn('order_items', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_points_item')->default(false);
                $table->unsignedInteger('points_cost')->nullable();
            }
        });
    }

    private function alterDeliveryPeople(): void
    {
        Schema::table('delivery_people', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_people', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'cpf')) {
                $table->string('cpf', 14)->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'cnh')) {
                $table->string('cnh', 20)->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'vehicle_plate')) {
                $table->string('vehicle_plate', 10)->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'vehicle_model')) {
                $table->string('vehicle_model')->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'avatar_path')) {
                $table->string('avatar_path')->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'password')) {
                $table->string('password')->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'invite_token')) {
                $table->string('invite_token', 80)->unique()->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'invite_expires_at')) {
                $table->timestamp('invite_expires_at')->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'invited_at')) {
                $table->timestamp('invited_at')->nullable();
            }
            if (! Schema::hasColumn('delivery_people', 'activated_at')) {
                $table->timestamp('activated_at')->nullable();
            }
        });
    }

    private function alterProducts(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
            if (! Schema::hasColumn('products', 'points_price')) {
                $table->decimal('points_price', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('products', 'stock')) {
                $table->integer('stock')->default(0)->unsigned();
            }
        });
    }

    private function alterProductAttributes(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('product_attributes', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }
        });
    }

    private function alterProductAttributeOptions(): void
    {
        Schema::table('product_attribute_options', function (Blueprint $table) {
            if (! Schema::hasColumn('product_attribute_options', 'ingredient_id')) {
                $table->foreignId('ingredient_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    private function alterTables(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            if (! Schema::hasColumn('tables', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
        });
    }

    private function alterPayments(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
                $table->string('efi_charge_id')->nullable();
                $table->string('efi_pix_txid')->nullable();
            }
        });
    }

    private function alterCoupons(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
        });
    }

    private function alterCategories(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
        });
    }

    private function alterIngredients(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            if (! Schema::hasColumn('ingredients', 'uuid')) {
                $table->uuid('uuid')->unique()->nullable();
            }
        });
    }

    private function alterLoyaltyConfigs(): void
    {
        Schema::table('tenant_loyalty_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_loyalty_configs', 'min_points_order_value')) {
                $table->decimal('min_points_order_value', 10, 2)->default(10.00);
            }
            if (! Schema::hasColumn('tenant_loyalty_configs', 'points_to_money_rate')) {
                $table->decimal('points_to_money_rate', 10, 4)->default(0.01);
            }
        });
    }

    private function createStockMovements(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('quantity')->comment('Positive = entry, Negative = exit');
                $table->integer('stock_before')->unsigned();
                $table->integer('stock_after')->unsigned();
                $table->string('type');
                $table->string('description')->nullable();
                $table->string('idempotency_key')->nullable()->unique();
                $table->timestamps();
                $table->index(['tenant_id', 'product_id', 'created_at']);
                $table->index(['order_id']);
            });
        }
    }

    private function createPasswordResets(): void
    {
        if (! Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
                $table->unique(['email', 'tenant_id']);
            });
        }
    }

    private function createIndexes(): void
    {
        $this->createIndex('orders', 'orders_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndex('orders', 'orders_tenant_id_type_status_index', ['tenant_id', 'type', 'status']);
        $this->createIndex('orders', 'orders_tenant_id_user_id_index', ['tenant_id', 'user_id']);
        $this->createIndex('orders', 'orders_tenant_id_created_at_index', ['tenant_id', 'created_at']);
        $this->createIndex('orders', 'orders_tenant_id_table_id_index', ['tenant_id', 'table_id']);
        $this->createIndex('orders', 'orders_tenant_id_payment_status_index', ['tenant_id', 'payment_status']);
        $this->createIndex('products', 'products_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndex('products', 'products_tenant_id_category_id_index', ['tenant_id', 'category_id']);
        $this->createIndex('tables', 'tables_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndex('payments', 'payments_tenant_id_status_index', ['tenant_id', 'status']);
        $this->createIndex('payments', 'payments_order_id_status_index', ['order_id', 'status']);
        $this->createIndex('users', 'users_tenant_id_role_index', ['tenant_id', 'role']);
    }

    private function createIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasIndex($table, $name)) {
            Schema::table($table, fn ($t) => $t->index($columns, $name));
        }
    }
};
