<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'classic-burger-artisan')->firstOrFail();

        $tables = [
            ['number' => '1', 'capacity' => 4, 'status' => 'free'],
            ['number' => '2', 'capacity' => 4, 'status' => 'free'],
            ['number' => '3', 'capacity' => 4, 'status' => 'free'],
            ['number' => '4', 'capacity' => 4, 'status' => 'occupied'],
            ['number' => '5', 'capacity' => 4, 'status' => 'free'],
            ['number' => '6', 'capacity' => 4, 'status' => 'reserved'],
            ['number' => '7', 'capacity' => 4, 'status' => 'free'],
            ['number' => '8', 'capacity' => 4, 'status' => 'free'],
        ];

        foreach ($tables as $table) {
            Table::firstOrCreate(
                ['number' => $table['number'], 'tenant_id' => $tenant->id],
                array_merge($table, ['tenant_id' => $tenant->id])
            );
        }
    }
}
