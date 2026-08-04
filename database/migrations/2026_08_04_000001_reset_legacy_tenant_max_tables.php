<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Até o refactor de limites dinâmicos, tenants.max_tables recebia o
     * default do plano copiado na criação/upgrade (ex.: 2 no Gratuito,
     * 50 no Premium). Com a coluna virando override opcional (NULL = usar o
     * limite do plano), esses valores legados silenciosamente ignoravam as
     * edições de plano feitas pelo superadmin. Zera os valores antigos para
     * que todos os tenants voltem a seguir o plano ativo.
     */
    public function up(): void
    {
        DB::table('tenants')->update(['max_tables' => null]);
    }

    public function down(): void
    {
        // Migração de dados: não há como restaurar os valores anteriores.
    }
};
