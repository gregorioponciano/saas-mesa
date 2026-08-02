<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RenewTenantSubscription;
use App\Jobs\SuspendTenantAccess;
use App\Models\SaasSubscription;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptions extends Command
{
    protected $signature = 'saas:check-subscriptions';

    protected $description = 'Verifica e gerencia o ciclo de vida das assinaturas';

    public function handle(SubscriptionService $subscriptionService, SaasEfiBankService $efiBankService): int
    {
        $this->info('Verificando assinaturas...');

        // 1. Suspender tenants com assinatura vencida
        $suspensionDays = config('efibank.suspension_after_days', 5);
        $suspensionCutoff = now()->subDays($suspensionDays);

        $pastDueSubscriptions = SaasSubscription::whereIn('status', ['past_due', 'trial'])
            ->where(function ($q) {
                $q->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '<', now());
            })
            ->where('current_period_end', '<', $suspensionCutoff)
            ->whereNull('suspended_at')
            ->get();

        $suspendedCount = 0;
        foreach ($pastDueSubscriptions as $subscription) {
            SuspendTenantAccess::dispatch($subscription->tenant, 'automatic_suspension')
                ->onQueue('subscriptions');
            $suspendedCount++;
        }

        $this->info("{$suspendedCount} tenants marcados para suspensão.");

        // 2. Tentar reativar tenants suspensos com pagamento recente
        $reactivatedCount = 0;
        $suspendedSubscriptions = SaasSubscription::where('status', 'suspended')
            ->with('tenant')
            ->get();

        foreach ($suspendedSubscriptions as $subscription) {
            try {
                $status = $efiBankService->verifySubscriptionStatus($subscription);
                if ($status === 'active') {
                    $subscriptionService->reactivateTenant($subscription->tenant);
                    $reactivatedCount++;
                }
            } catch (\Throwable $e) {
                Log::warning('Falha ao verificar assinatura suspensa', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("{$reactivatedCount} tenants reativados.");

        // 3. Renovar assinaturas próximas do vencimento
        $renewalWindow = now()->addDays(3);
        $dueForRenewal = SaasSubscription::where('status', 'active')
            ->where('next_billing_date', '<=', $renewalWindow)
            ->get();

        $renewCount = 0;
        foreach ($dueForRenewal as $subscription) {
            RenewTenantSubscription::dispatch($subscription)
                ->onQueue('subscriptions');
            $renewCount++;
        }

        $this->info("{$renewCount} assinaturas marcadas para renovação.");

        Log::info('CheckSubscriptions concluído', [
            'suspended' => $suspendedCount,
            'reactivated' => $reactivatedCount,
            'renewed' => $renewCount,
        ]);

        return Command::SUCCESS;
    }
}
