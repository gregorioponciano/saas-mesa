<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SuspendTenantAccess;
use App\Models\SaasSubscription;
use App\Services\PointsService;
use Illuminate\Support\Facades\Log;

class SaasSubscriptionObserver
{
    public function updated(SaasSubscription $subscription): void
    {
        $originalStatus = $subscription->getOriginal('status');
        $newStatus = $subscription->status;

        if ($originalStatus === 'active' && $newStatus === 'past_due') {
            $suspensionDays = config('efibank.suspension_after_days', 5);
            $suspensionDate = now()->addDays($suspensionDays);

            SuspendTenantAccess::dispatch($subscription->tenant, 'payment_overdue')
                ->delay($suspensionDate)
                ->onQueue('subscriptions');

            Log::info('Subscription past due, suspension scheduled', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'suspension_date' => $suspensionDate,
            ]);
        }

        if ($newStatus === 'active' && $originalStatus === 'suspended') {
            $tenant = $subscription->tenant;
            if ($tenant && $tenant->status === 'suspended') {
                $tenant->update(['status' => 'active']);
            }

            Log::info('Subscription reactivated', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
            ]);
        }
    }

    public function created(SaasSubscription $subscription): void
    {
        Log::info('Subscription created', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status,
        ]);
    }

    public function deleted(SaasSubscription $subscription): void
    {
        Log::info('Subscription deleted', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }
}
