<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = "BASE TABLE"');

        foreach ($tables as $table) {
            $cols = DB::select(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND data_type IN (?, ?)',
                [$table->TABLE_NAME, 'datetime', 'timestamp']
            );

            foreach ($cols as $col) {
                DB::statement("UPDATE `{$table->TABLE_NAME}` SET `{$col->COLUMN_NAME}` = DATE_SUB(`{$col->COLUMN_NAME}`, INTERVAL 3 HOUR) WHERE `{$col->COLUMN_NAME}` IS NOT NULL");
            }
        }
    }

    public function down(): void
    {
        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = "BASE TABLE"');

        foreach ($tables as $table) {
            $cols = DB::select(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND data_type IN (?, ?)',
                [$table->TABLE_NAME, 'datetime', 'timestamp']
            );

            foreach ($cols as $col) {
                DB::statement("UPDATE `{$table->TABLE_NAME}` SET `{$col->COLUMN_NAME}` = DATE_ADD(`{$col->COLUMN_NAME}`, INTERVAL 3 HOUR) WHERE `{$col->COLUMN_NAME}` IS NOT NULL");
            }
        }
    }
};
