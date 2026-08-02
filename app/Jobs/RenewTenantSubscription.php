<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SaasSubscription;
use App\Services\EfiBank\SaasEfiBankService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenewTenantSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly SaasSubscription $subscription
    ) {}

    public function handle(SaasEfiBankService $efiBankService): void
    {
        try {
            $plan = $this->subscription->plan;
            $tenant = $this->subscription->tenant;

            if (! $plan || ! $tenant) {
                Log::warning('Renewal skipped: plan or tenant not found', [
                    'subscription_id' => $this->subscription->id,
                ]);

                return;
            }

            // Cria nova cobrança PIX para o próximo mês
            DB::transaction(function () use ($efiBankService, $plan, $tenant) {
                $response = $efiBankService->createSubscriptionCharge($tenant, $plan, $this->subscription);

                if ($response) {
                    $this->subscription->update([
                        'current_period_start' => now(),
                        'current_period_end' => now()->addMonth(),
                        'next_billing_date' => now()->addMonth(),
                        'status' => 'pending',
                        'suspended_at' => null,
                    ]);

                    Log::info('Monthly renewal charge created', [
                        'subscription_id' => $this->subscription->id,
                        'tenant_id' => $tenant->id,
                        'charge_id' => $response['efi_charge_id'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Failed to renew tenant subscription', [
                'subscription_id' => $this->subscription->id,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
