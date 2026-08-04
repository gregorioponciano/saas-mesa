<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\OrderPaid;
use App\Models\OrderPayment;
use App\Models\SaasPaymentHistory;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\WebhookLog;
use App\Services\TenantResolverService;
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

        if (! $log) {
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

        if (! $identifier) {
            Log::warning('Saas webhook: missing identifier', ['payload' => $payload]);

            return;
        }

        $subscription = SaasSubscription::where('efi_charge_id', $identifier)->first();

        if (! $subscription) {
            Log::warning('Saas webhook: subscription not found', ['identifier' => $identifier]);

            return;
        }

        $event = $payload['event'] ?? $payload['status'] ?? '';

        $isPaid = in_array($event, ['payment_confirmed', 'paid', 'charge.completed'])
            || $event === 'pix'
            || isset($payload['pix']);

        if ($isPaid) {
            $amountError = $this->validatePaidAmount($subscription, $pixData);

            if ($amountError !== null) {
                Log::error('Saas webhook: paid amount mismatch', [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $subscription->tenant_id,
                    'error' => $amountError,
                ]);

                throw new \RuntimeException($amountError);
            }

            $months = $subscription->metadata['months'] ?? 1;

            $hasPeriod = $subscription->current_period_end !== null && $subscription->current_period_end > now();
            $newPeriodEnd = $hasPeriod
                ? $subscription->current_period_end->copy()->addMonths($months)
                : now()->addMonths($months);

            $subscription->update([
                'status' => 'active',
                'current_period_start' => $subscription->current_period_start ?? now(),
                'current_period_end' => $newPeriodEnd,
                'next_billing_date' => $newPeriodEnd,
                'suspended_at' => null,
            ]);

            $tenant = $subscription->tenant;
            if ($tenant) {
                $plan = $subscription->plan;
                $isPaidPlan = $plan !== null && $plan->price_cents > 0;

                $tenant->update([
                    'status' => 'active',
                    'plan' => $isPaidPlan ? Tenant::PLAN_PAID : Tenant::PLAN_FREE,
                    'subscription_id' => $subscription->id,
                    'subscription_ends_at' => $newPeriodEnd,
                ]);

                app(TenantResolverService::class)->clearCache($tenant);
            }

            SaasPaymentHistory::updateOrCreate(
                ['efi_charge_id' => $identifier],
                [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $subscription->tenant_id,
                    'amount_cents' => $this->parsePaidCents($pixData),
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

    private function validatePaidAmount(SaasSubscription $subscription, array $pixData): ?string
    {
        $priceCents = (int) ($subscription->plan?->price_cents ?? 0);

        if ($priceCents <= 0) {
            return null;
        }

        $paidCents = $this->parsePaidCents($pixData);

        if ($paidCents <= 0) {
            return 'Valor pago não informado no payload do webhook';
        }

        $months = (int) ($subscription->metadata['months'] ?? 1);
        $expectedCents = $priceCents * max($months, 1);

        $matches = abs($paidCents - $expectedCents) <= 1;

        if (! $matches && $paidCents % $priceCents === 0) {
            $matches = true;
        }

        if (! $matches) {
            return sprintf(
                'Valor pago (R$ %.2f) não corresponde ao plano (esperado R$ %.2f para %d mês/meses)',
                $paidCents / 100,
                $expectedCents / 100,
                $months
            );
        }

        return null;
    }

    private function parsePaidCents(array $pixData): int
    {
        $valor = (string) ($pixData['valor'] ?? $pixData['value'] ?? '');

        if ($valor === '') {
            return 0;
        }

        $normalized = str_replace(['.', ','], ['.', '.'], trim($valor));

        return (int) round((float) $normalized * 100);
    }

    private function processTenantWebhook(array $payload): void
    {
        $txid = $payload['pix'][0]['txid'] ?? $payload['txid'] ?? null;

        if (! $txid) {
            Log::warning('Tenant webhook: missing txid', ['payload' => $payload]);

            return;
        }

        $payment = OrderPayment::where('efi_pix_txid', $txid)
            ->lockForUpdate()
            ->first();

        if (! $payment) {
            Log::warning('Tenant webhook: payment not found', ['txid' => $txid]);

            return;
        }

        if ($this->tenantId !== null && $payment->tenant_id !== $this->tenantId) {
            Log::warning('Tenant webhook: txid belongs to another tenant, ignoring', [
                'txid' => $txid,
                'webhook_tenant_id' => $this->tenantId,
                'payment_tenant_id' => $payment->tenant_id,
            ]);

            return;
        }

        $order = $payment->order;

        if ($this->tenantId !== null && $order && $order->tenant_id !== $this->tenantId) {
            Log::warning('Tenant webhook: order belongs to another tenant, ignoring', [
                'txid' => $txid,
                'webhook_tenant_id' => $this->tenantId,
                'order_tenant_id' => $order->tenant_id,
                'payment_id' => $payment->id,
            ]);

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
