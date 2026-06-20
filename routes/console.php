<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Tarefas Agendadas do SaaS ───────────────────────────────────────────

// Verificar assinaturas vencidas e suspender a cada hora
Schedule::command('saas:check-subscriptions')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler-check-subscriptions.log'));

// Gerar relatório financeiro no primeiro dia de cada mês
Schedule::command('saas:financial-report')
    ->monthlyOn(1, '06:00')
    ->appendOutputTo(storage_path('logs/scheduler-financial-report.log'));
