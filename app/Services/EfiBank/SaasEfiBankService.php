<?php

declare(strict_types=1);

namespace App\Services\EfiBank;

use App\Models\SaasPaymentHistory;
use App\Models\SaasPixCharge;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SaasEfiBankService
{
    private EfiBankClient $client;

    public function __construct(?EfiBankClient $client = null)
    {
        $this->client = $client ?? EfiBankClient::forSaas();
    }

    public function createPlanOnEfi(SaasPlan $plan): array
    {
        $body = [
            'name' => $plan->name,
            'interval' => 1,
            'repeats' => null,
        ];

        $response = $this->client->createPlan($body);

        Log::info('EfiBank plan created', [
            'plan_id' => $plan->id,
            'efi_response' => $response,
        ]);

        return $response;
    }

    public function createSubscription(Tenant $tenant, SaasPlan $plan, array $paymentData = []): SaasSubscription
    {
        $months = $paymentData['months'] ?? 1;

        return DB::transaction(function () use ($tenant, $plan, $months) {
            $subscription = SaasSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'payment_method' => 'pix',
                'trial_ends_at' => now()->addDays(7),
                'current_period_start' => null,
                'current_period_end' => null,
                'next_billing_date' => null,
            ]);

            $this->chargeSubscription($subscription, $tenant, $plan, $months);

            return $subscription->fresh();
        });
    }

    public function createSubscriptionCharge(Tenant $tenant, SaasPlan $plan, SaasSubscription $subscription, int $months = 1): ?array
    {
        try {
            $this->chargeSubscription($subscription, $tenant, $plan, $months);

            $metadata = $subscription->fresh()->metadata ?? [];

            return [
                'efi_txid' => $metadata['efi_txid'] ?? null,
                'pix_qrcode' => $metadata['pix_qrcode'] ?? null,
                'pix_copy_paste' => $metadata['pix_copy_paste'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to create renewal PIX charge', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'months' => $months,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    public function chargeSubscription(SaasSubscription $subscription, Tenant $tenant, SaasPlan $plan, int $months = 1): void
    {
        $pixKey = $this->getPixKey();
        $txid = strtolower(Str::random(26));
        $totalCents = $plan->getTotalForMonths($months);

        $expiresAt = now()->addHour();
        $body = [
            'calendario' => [
                'expiracao' => 3600,
            ],
            'devedor' => [
                'cpf' => $this->generateValidCpf(),
                'nome' => $tenant->name,
            ],
            'valor' => [
                'original' => number_format($totalCents / 100, 2, '.', ''),
            ],
            'chave' => $pixKey,
            'solicitacaoPagador' => 'Assinatura '.$plan->name.' - '.$tenant->slug.' ('.$months.' meses)',
        ];

        Log::info('Creating PIX charge for subscription', [
            'subscription_id' => $subscription->id,
            'txid' => $txid,
            'value' => $body['valor']['original'],
            'pix_key' => substr($pixKey, 0, 8).'...',
        ]);

        $response = $this->client->pixCreateImmediateCharge($txid, $body);

        $locId = $response['loc']['id'] ?? null;

        $copyPaste = $response['pixCopiaECola'] ?? null;

        if ($locId && empty($copyPaste)) {
            try {
                $qrResponse = $this->client->pixGetQRCode($locId);
                $copyPaste = $qrResponse['qrcode'] ?? null;
            } catch (\Throwable $e) {
                Log::warning('Failed to get PIX QR code from EfiBank', [
                    'loc_id' => $locId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $pixQrCode = ! empty($copyPaste) ? EfiBankClient::generateQrCodeBase64($copyPaste) : null;

        $pixData = [
            'txid' => $txid,
            'pix_qrcode' => $pixQrCode,
            'pix_copy_paste' => $copyPaste,
            'loc_id' => $locId,
            'location' => $response['loc']['location'] ?? null,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        $subscription->update([
            'efi_charge_id' => $txid,
            'metadata' => array_merge($subscription->metadata ?? [], $pixData, ['months' => $months]),
        ]);

        SaasPixCharge::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'txid' => $txid,
            'loc_id' => $locId !== null ? (string) $locId : null,
            'amount_cents' => $totalCents,
            'months' => $months,
            'status' => 'pending',
            'qrcode' => $pixQrCode,
            'copy_paste' => $copyPaste,
            'expires_at' => $expiresAt,
        ]);

        Log::info('PIX charge created successfully', [
            'subscription_id' => $subscription->id,
            'txid' => $txid,
            'has_qrcode' => ! empty($pixData['pix_qrcode']),
            'has_copy_paste' => ! empty($pixData['pix_copy_paste']),
        ]);
    }

    public function pixDetails(SaasSubscription $subscription): array
    {
        $metadata = $subscription->metadata ?? [];

        $expiresAt = ! empty($metadata['expires_at'])
            ? Carbon::parse($metadata['expires_at'])
            : null;
        $expired = $expiresAt && now()->isAfter($expiresAt);

        if ($expired) {
            return [
                'expired' => true,
                'expires_at' => $expiresAt,
                'qrcode' => null,
                'copy_paste' => null,
                'txid' => $metadata['txid'] ?? null,
            ];
        }

        $emv = $metadata['pix_copy_paste'] ?? null;

        // Se pix_qrcode já é uma imagem PNG (base64), usa direto; senão gera a partir do EMV
        $qrImage = $metadata['pix_qrcode'] ?? null;
        if ($qrImage && ! str_starts_with($qrImage, 'iVBOR')) {
            $qrImage = null; // não é PNG, provavelmente é EMV
        }

        if ($emv && ! $qrImage) {
            $qrImage = EfiBankClient::generateQrCodeBase64($emv);
            $subscription->update([
                'metadata' => array_merge($metadata, ['pix_qrcode' => $qrImage]),
            ]);
        }

        if ($qrImage || $emv) {
            return [
                'expired' => false,
                'expires_at' => $expiresAt,
                'qrcode' => $qrImage,
                'copy_paste' => $emv,
                'txid' => $metadata['txid'] ?? null,
                'location' => $metadata['location'] ?? null,
            ];
        }

        if (! empty($metadata['loc_id'])) {
            try {
                $response = $this->client->pixGetQRCode((int) $metadata['loc_id']);
                $emv = $response['qrcode'] ?? null;

                if ($emv) {
                    $qrImage = EfiBankClient::generateQrCodeBase64($emv);
                    $subscription->update([
                        'metadata' => array_merge($metadata, [
                            'pix_qrcode' => $qrImage,
                            'pix_copy_paste' => $emv,
                        ]),
                    ]);

                    return [
                        'expired' => false,
                        'expires_at' => $expiresAt,
                        'qrcode' => $qrImage,
                        'copy_paste' => $emv,
                        'txid' => $metadata['txid'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch PIX QR code from EfiBank', [
                    'subscription_id' => $subscription->id,
                    'loc_id' => $metadata['loc_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [];
    }

    public function cancelSubscription(SaasSubscription $subscription): void
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function verifySubscriptionStatus(SaasSubscription $subscription): string
    {
        if (! $subscription->efi_charge_id) {
            return $subscription->status;
        }

        try {
            $txid = $subscription->efi_charge_id;
            $response = $this->client->pixGetCharge($txid);
            $status = $response['status'] ?? '';

            return match ($status) {
                'CONCLUIDA', 'ATIVA' => 'active',
                'REMOVIDA_PELO_USUARIO_RECEBEDOR' => 'cancelled',
                default => $subscription->status,
            };
        } catch (\Throwable $e) {
            Log::error('Failed to verify PIX charge status', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return $subscription->status;
        }
    }

    public function processSaasWebhook(array $payload): void
    {
        // Suporta formato PIX (txid) e formato Charge (charge_id)
        $pixData = $payload['pix'][0] ?? $payload;
        $txid = $pixData['txid'] ?? $payload['txid'] ?? null;
        $chargeId = $payload['charge_id'] ?? $payload['id'] ?? null;

        $identifier = $txid ?? $chargeId;

        if (! $identifier) {
            Log::warning('Saas webhook received without txid or charge_id', ['payload' => $payload]);

            return;
        }

        $subscription = SaasSubscription::where('efi_charge_id', $identifier)->first();

        if (! $subscription) {
            Log::warning('Saas webhook: subscription not found', ['identifier' => $identifier]);

            return;
        }

        $event = $payload['event'] ?? $payload['status'] ?? '';

        DB::transaction(function () use ($subscription, $event, $payload, $identifier) {
            $isPaid = in_array($event, ['payment_confirmed', 'paid', 'charge.completed'])
                || $event === 'pix'
                || isset($payload['pix']);

            if ($isPaid) {
                $months = $subscription->metadata['months'] ?? 1;
                $subscription->update([
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonths($months),
                    'next_billing_date' => now()->addMonths($months),
                    'suspended_at' => null,
                ]);

                if ($subscription->tenant) {
                    $tenantPlan = $subscription->plan?->slug === 'free' ? 'free' : 'paid';
                    $subscription->tenant->update([
                        'status' => 'active',
                        'plan' => $tenantPlan,
                    ]);
                }

                $amountCents = $subscription->plan?->getTotalForMonths($subscription->metadata['months'] ?? 1) ?? 0;
                SaasPaymentHistory::updateOrCreate(
                    ['efi_charge_id' => $identifier],
                    [
                        'subscription_id' => $subscription->id,
                        'tenant_id' => $subscription->tenant_id,
                        'amount_cents' => $amountCents,
                        'status' => 'paid',
                        'method' => 'pix',
                        'paid_at' => now(),
                    ]
                );

                SaasPixCharge::where('txid', $identifier)
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

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
        });
    }

    public function getPixKey(): string
    {
        return $this->client->getConfig()['pix_key'] ?? '';
    }

    private function generateValidCpf(): string
    {
        $n = [];
        for ($i = 0; $i < 9; $i++) {
            $n[] = random_int(0, 9);
        }
        $s = 0;
        for ($i = 0; $i < 9; $i++) {
            $s += $n[$i] * (10 - $i);
        }
        $r = ($s % 11 < 2) ? 0 : 11 - ($s % 11);
        $n[] = $r;
        $s = 0;
        for ($i = 0; $i < 10; $i++) {
            $s += $n[$i] * (11 - $i);
        }
        $r = ($s % 11 < 2) ? 0 : 11 - ($s % 11);
        $n[] = $r;

        return implode('', $n);
    }
}
