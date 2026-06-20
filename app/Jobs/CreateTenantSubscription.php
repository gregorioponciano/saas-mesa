<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateTenantSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly SaasPlan $plan
    ) {}

    public function handle(SubscriptionService $subscriptionService): void
    {
        try {
            $subscription = $subscriptionService->createInitialSubscription($this->tenant, $this->plan);

            Log::info('Tenant subscription created', [
                'tenant_id' => $this->tenant->id,
                'plan_id' => $this->plan->id,
                'subscription_id' => $subscription->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create tenant subscription', [
                'tenant_id' => $this->tenant->id,
                'plan_id' => $this->plan->id,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
