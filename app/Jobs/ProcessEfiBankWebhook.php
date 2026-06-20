<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OrderPayment;
use App\Models\SaasPaymentHistory;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\WebhookLog;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Events\OrderPaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessEfiBankWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $logId,
        private readonly string $source,
        private readonly ?int $tenantId = null
    ) {}

    public function handle(): void
    {
        $log = WebhookLog::find($this->logId);

        if (!$log) {
            Log::warning('Webhook log not found', ['log_id' => $this->logId]);
            return;
        }

        if ($log->processed) {
            return;
        }

        try {
            $payload = json_decode($log->payload_json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON payload');
            }

            DB::transaction(function () use ($log, $payload) {
                match ($this->source) {
                    'saas' => $this->processSaasWebhook($payload),
                    'tenant' => $this->processTenantWebhook($payload),
                    default => throw new \InvalidArgumentException("Unknown webhook source: {$this->source}"),
                };

                $log->update(['processed' => true]);
            });

            Log::info('Webhook processed successfully', [
                'log_id' => $this->logId,
                'source' => $this->source,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Webhook processing failed', [
                'log_id' => $this->logId,
                'source' => $this->source,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    private function processSaasWebhook(array $payload): void
    {
        $pixData = $payload['pix'][0] ?? $payload;
        $txid = $pixData['txid'] ?? $payload['txid'] ?? null;
        $chargeId = $payload['charge_id'] ?? $payload['id'] ?? null;
        $identifier = $txid ?? $chargeId;

        if (!$identifier) {
            Log::warning('Saas webhook: missing identifier', ['payload' => $payload]);
            return;
        }

        $subscription = SaasSubscription::where('efi_charge_id', $identifier)->first();

        if (!$subscription) {
            Log::warning('Saas webhook: subscription not found', ['identifier' => $identifier]);
            return;
        }

        $event = $payload['event'] ?? $payload['status'] ?? '';

        $isPaid = in_array($event, ['payment_confirmed', 'paid', 'charge.completed'])
            || $event === 'pix'
            || isset($payload['pix']);

        if ($isPaid) {
            $subscription->update([
                'status' => 'active',
                'current_period_start' => $subscription->current_period_start ?? now(),
                'current_period_end' => now()->addMonth(),
                'next_billing_date' => now()->addMonth(),
                'suspended_at' => null,
            ]);

            $tenant = $subscription->tenant;
            if ($tenant && $tenant->status === 'suspended') {
                $tenant->update(['status' => 'active']);
            }

            SaasPaymentHistory::updateOrCreate(
                ['efi_charge_id' => $identifier],
                [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $subscription->tenant_id,
                    'amount_cents' => $subscription->plan?->price_cents ?? 0,
                    'status' => 'paid',
                    'method' => 'pix',
                    'paid_at' => now(),
                ]
            );

            Log::info('Saas subscription activated via webhook', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'identifier' => $identifier,
            ]);
        } elseif (in_array($event, ['payment_failed', 'charge.failed', 'canceled'])) {
            $subscription->update(['status' => 'past_due']);

            Log::warning('Saas subscription payment failed', [
                'subscription_id' => $subscription->id,
                'identifier' => $identifier,
                'event' => $event,
            ]);
        }
    }

    private function processTenantWebhook(array $payload): void
    {
        $txid = $payload['pix'][0]['txid'] ?? $payload['txid'] ?? null;

        if (!$txid) {
            Log::warning('Tenant webhook: missing txid', ['payload' => $payload]);
            return;
        }

        $payment = OrderPayment::where('efi_pix_txid', $txid)
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            Log::warning('Tenant webhook: payment not found', ['txid' => $txid]);
            return;
        }

        if ($payment->isPaid()) {
            return;
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'webhook_received_at' => now(),
        ]);

        $order = $payment->order;
        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            OrderPaid::dispatch($order);
        }

        Log::info('Order payment confirmed via webhook', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'tenant_id' => $payment->tenant_id,
            'txid' => $txid,
        ]);
    }
}
