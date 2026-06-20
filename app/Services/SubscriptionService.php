<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Services\EfiBank\SaasEfiBankService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(
        private readonly SaasEfiBankService $efiBankService,
        private readonly TenantResolverService $tenantResolver
    ) {}

    public function createInitialSubscription(Tenant $tenant, SaasPlan $plan): SaasSubscription
    {
        $subscription = $this->efiBankService->createSubscription($tenant, $plan);

        $tenant->update([
            'plan' => $plan->slug === 'premium' ? 'paid' : 'free',
            'max_tables' => $plan->features_json['max_tables'] ?? 10,
            'status' => 'active',
            'subscription_id' => $subscription->id,
            'trial_ends_at' => $subscription->trial_ends_at,
            'subscription_ends_at' => $subscription->current_period_end,
        ]);

        $this->tenantResolver->clearCache($tenant);

        return $subscription;
    }

    public function suspendTenant(Tenant $tenant, string $reason = 'payment_overdue'): void
    {
        DB::transaction(function () use ($tenant, $reason) {
            $tenant->update(['status' => 'suspended']);

            $subscription = SaasSubscription::where('tenant_id', $tenant->id)
                ->whereIn('status', ['active', 'trial', 'past_due'])
                ->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'suspended',
                    'suspended_at' => now(),
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'suspension_reason' => $reason,
                        'suspended_by' => 'system',
                    ]),
                ]);
            }

            Log::info('Tenant suspended', [
                'tenant_id' => $tenant->id,
                'reason' => $reason,
            ]);
        });
    }

    public function reactivateTenant(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active']);

            $subscription = SaasSubscription::where('tenant_id', $tenant->id)
                ->where('status', 'suspended')
                ->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'active',
                    'suspended_at' => null,
                ]);
            }

            Log::info('Tenant reactivated', [
                'tenant_id' => $tenant->id,
            ]);
        });
    }

    public function checkAndSuspendPastDue(): int
    {
        $suspensionDays = config('efibank.suspension_after_days', 5);
        $cutoff = now()->subDays($suspensionDays);
        $suspendedCount = 0;

        $pastDueSubscriptions = SaasSubscription::whereIn('status', ['past_due', 'trial'])
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('trial_ends_at')
                  ->orWhere('trial_ends_at', '<', now());
            })
            ->where('current_period_end', '<', $cutoff)
            ->get();

        foreach ($pastDueSubscriptions as $subscription) {
            $this->suspendTenant($subscription->tenant, 'automatic_suspension');
            $suspendedCount++;
        }

        return $suspendedCount;
    }

    public function checkAndReactivatePaid(): int
    {
        $reactivatedCount = 0;

        $suspendedSubscriptions = SaasSubscription::where('status', 'suspended')
            ->with('tenant')
            ->get();

        foreach ($suspendedSubscriptions as $subscription) {
            try {
                $efiStatus = $this->efiBankService->verifySubscriptionStatus($subscription);

                if ($efiStatus === 'active') {
                    $this->reactivateTenant($subscription->tenant);
                    $reactivatedCount++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to verify subscription for reactivation', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $reactivatedCount;
    }
}
