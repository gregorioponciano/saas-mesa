<?php

declare(strict_types=1);

it('classifica os planos corretamente', function () {
    $free = createTenant(['plan' => 'free']);
    $paid = createTenant(['plan' => 'paid']);

    expect($free->isFree())->toBeTrue()
        ->and($free->isPaid())->toBeFalse()
        ->and($paid->isPaid())->toBeTrue()
        ->and($paid->isFree())->toBeFalse();
});

it('limita o número de mesas por plano', function () {
    $free = createTenant(['plan' => 'free', 'max_tables' => 2]);
    $paid = createTenant(['plan' => 'paid', 'max_tables' => 30]);

    expect($free->maxTablesAllowed())->toBe(2)
        ->and($paid->maxTablesAllowed())->toBe(50);

    $free->tables()->create(['number' => '1']);
    $free->tables()->create(['number' => '2']);
    $free->tables()->create(['number' => '3']);

    expect($free->canAddTable())->toBeFalse()
        ->and($free->hasHiddenTables())->toBeTrue()
        ->and($free->hiddenTablesCount())->toBe(1);
});
