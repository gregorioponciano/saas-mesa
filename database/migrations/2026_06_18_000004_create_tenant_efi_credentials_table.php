<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_efi_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('client_id_encrypted');
            $table->text('client_secret_encrypted');
            $table->text('pix_key_encrypted')->nullable();
            $table->string('account_type', 20)->default('sandbox');
            $table->text('certificate_path_encrypted')->nullable();
            $table->text('certificate_content_encrypted')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_efi_credentials');
    }
};
