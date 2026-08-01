<?php

declare(strict_types=1);

use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('points_transactions possui indice composto (tenant_id, user_id, created_at)', function () {
    expect(Schema::hasIndex('points_transactions', 'points_transactions_tenant_user_created_idx'))->toBeTrue();
});

test('consulta de pontos recentes usa o indice composto sem filesort', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    PointsTransaction::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'points' => 10,
        'type' => PointsTransaction::TYPE_EARNED,
        'description' => 'test',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $plan = DB::select(
        "EXPLAIN QUERY PLAN SELECT * FROM points_transactions
         WHERE tenant_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 10",
        [$tenant->id, $user->id],
    );

    $planText = implode(' ', array_map(fn ($row) => $row->detail, $plan));

    expect($planText)->toContain('points_transactions_tenant_user_created_idx');
});
