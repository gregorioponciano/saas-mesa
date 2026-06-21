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
            if (!Schema::hasColumn('tenants', 'logo_width')) {
                $table->unsignedSmallInteger('logo_width')->default(44)->after('logo');
            }
            if (!Schema::hasColumn('tenants', 'logo_height')) {
                $table->unsignedSmallInteger('logo_height')->default(44)->after('logo_width');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'logo_width')) {
                $table->dropColumn(['logo_width', 'logo_height']);
            }
        });
    }
};
