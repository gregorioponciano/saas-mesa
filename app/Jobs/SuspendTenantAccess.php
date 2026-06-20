<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SuspendTenantAccess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $reason = 'payment_overdue'
    ) {}

    public function handle(SubscriptionService $subscriptionService): void
    {
        try {
            $subscriptionService->suspendTenant($this->tenant, $this->reason);

            Log::info('Tenant access suspended', [
                'tenant_id' => $this->tenant->id,
                'reason' => $this->reason,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to suspend tenant access', [
                'tenant_id' => $this->tenant->id,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
