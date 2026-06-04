<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create ingredients table (tenant-scoped)
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // 2. Add ingredient_id to product_attribute_options
        Schema::table('product_attribute_options', function (Blueprint $table) {
            $table->foreignId('ingredient_id')->nullable()->constrained()->nullOnDelete();
        });

        // 3. Add price to product_attributes
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('is_required');
        });
    }

    public function down(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('product_attribute_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ingredient_id');
        });

        Schema::dropIfExists('ingredients');
    }
};
