<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SaasPaymentHistory;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateFinancialReport extends Command
{
    protected $signature = 'saas:financial-report {--month= : Mês para gerar relatório (Y-m)}';

    protected $description = 'Gera relatório financeiro mensal do SaaS';

    public function handle(): int
    {
        $month = $this->option('month') ?? now()->format('Y-m');
        $periodStart = Carbon::parse($month.'-01')->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        $this->info("Gerando relatório financeiro para {$month}...");

        $totalTenants = Tenant::count();
        $activeTenants = Tenant::whereIn('status', ['active', 'trial'])->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();

        $newTenants = Tenant::whereBetween('created_at', [$periodStart, $periodEnd])->count();

        $totalCollected = SaasPaymentHistory::where('status', 'paid')
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->sum('amount_cents');

        $paymentCount = SaasPaymentHistory::where('status', 'paid')
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->count();

        $mrr = SaasSubscription::whereIn('status', ['active', 'trial'])
            ->with('plan')
            ->get()
            ->sum(fn ($s) => $s->plan?->price_cents ?? 0);

        $report = [
            'period' => $month,
            'generated_at' => now()->toIso8601String(),
            'metrics' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'new_tenants' => $newTenants,
                'mrr_cents' => $mrr,
                'mrr_formatted' => 'R$ '.number_format($mrr / 100, 2, ',', '.'),
                'total_collected_cents' => $totalCollected,
                'total_collected_formatted' => 'R$ '.number_format($totalCollected / 100, 2, ',', '.'),
                'payment_count' => $paymentCount,
                'average_ticket_cents' => $paymentCount > 0 ? (int) ($totalCollected / $paymentCount) : 0,
            ],
        ];

        $this->table(
            ['Métrica', 'Valor'],
            collect($report['metrics'])->map(fn ($v, $k) => [$k, is_numeric($v) ? number_format((float) $v, $v === (int) $v ? 0 : 2, ',', '.') : $v])->toArray()
        );

        Log::info('Relatório financeiro gerado', $report);

        $this->info('Relatório gerado com sucesso!');

        return Command::SUCCESS;
    }
}
