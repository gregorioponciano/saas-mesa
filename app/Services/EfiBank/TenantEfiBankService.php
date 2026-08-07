<?php

declare(strict_types=1);

namespace App\Services\EfiBank;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Tenant;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantEfiBankService
{
    public function createPixCharge(Order $order, array $options = []): OrderPayment
    {
        $tenant = $order->tenant;
        $existingPayment = OrderPayment::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingPayment) {
            return $existingPayment;
        }

        $idempotencyKey = Str::uuid()->toString();
        $amountCents = (int) round(($order->total ?? 0) * 100);

        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'amount_cents' => $amountCents,
            'method' => 'pix',
            'status' => 'processing',
            'idempotency_key' => $idempotencyKey,
            'expires_at' => now()->addHour(),
        ]);

        try {
            $client = EfiBankClient::forTenant($tenant);
            $pixKey = $tenant->efiCredentials->decryptPixKey()
                ?? $client->getConfig()['pix_key']
                ?? '';

            $txid = Str::lower(Str::random(26));
            $txid = substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 26);
            $txid = str_pad($txid, 26, '0');

            $body = [
                'calendario' => ['expiracao' => 3600],
                'devedor' => [
                    'cpf' => $options['payer_cpf'] ?? $this->generateValidCpf(),
                    'nome' => $options['payer_name'] ?? ($order->customer_name ?: 'Cliente'),
                ],
                'valor' => ['original' => number_format($order->total, 2, '.', '')],
                'chave' => $pixKey,
                'solicitacaoPagador' => "Pedido #{$order->id}",
                'infoAdicionais' => [
                    ['nome' => 'Pedido', 'valor' => (string) $order->id],
                    ['nome' => 'Mesa', 'valor' => (string) ($order->table?->number ?? 'N/A')],
                ],
            ];

            $response = $client->pixCreateImmediateCharge($txid, $body);

            $payment->update([
                'status' => 'pending',
                'efi_pix_txid' => $response['txid'] ?? $txid,
                'qrcode' => $response['pixCopiaECola'] ?? null,
                'qrcode_image' => $response['imagemQrcode'] ?? null,
                'efi_response_raw' => $response,
            ]);

            return $payment->fresh();
        } catch (\Throwable $e) {
            $payment->update(['status' => 'error']);

            Log::error('Tenant PIX charge creation failed', [
                'order_id' => $order->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException($this->translateError($e));
        }
    }

    private function translateError(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if ($e instanceof RequestException || str_contains($msg, 'invalid_client')) {
            return 'Credenciais da Efí inválidas. Vá em Configurações > EfiBank e verifique seu Client ID, Client Secret e certificado.';
        }
        if (str_contains($msg, 'Falha ao ler certificado')) {
            return 'Certificado .p12 inválido. Faça upload do certificado correto em Configurações > EfiBank.';
        }
        if (str_contains($msg, 'Restaurante ainda não configurou')) {
            return 'Configure os dados bancários em Configurações > EfiBank antes de gerar PIX.';
        }
        if (str_contains($msg, 'Chave PIX não configurada')) {
            return 'Chave PIX não configurada. Vá em Configurações > EfiBank e cadastre sua chave PIX.';
        }
        if (str_contains($msg, 'token expirado') || str_contains($msg, 'unauthorized')) {
            return 'Sessão com a Efí expirou. Tente novamente.';
        }

        return $msg;
    }

    public function generatePixChargeData(Tenant $tenant, float $amount, string $txid, string $payerName = '', ?string $payerCpf = null): array
    {
        $credentials = $tenant->efiCredentials;
        if (! $credentials || ! $credentials->is_active) {
            throw new \RuntimeException('Restaurante ainda não configurou os dados bancários.');
        }

        $client = EfiBankClient::forTenant($tenant);
        $pixKey = $credentials->decryptPixKey() ?? '';

        if (empty($pixKey)) {
            throw new \RuntimeException('Chave PIX não configurada.');
        }

        $txid = substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 26);
        $txid = str_pad($txid, 26, '0');
        $cpf = $payerCpf ?: $this->generateValidCpf();

        $body = [
            'calendario' => ['expiracao' => 3600],
            'devedor' => [
                'cpf' => $cpf,
                'nome' => $payerName ?: 'Cliente',
            ],
            'valor' => ['original' => number_format($amount, 2, '.', '')],
            'chave' => $pixKey,
            'solicitacaoPagador' => "Pedido {$txid}",
        ];

        try {
            $response = $client->pixCreateImmediateCharge($txid, $body);
        } catch (\Throwable $e) {
            throw new \RuntimeException($this->translateError($e));
        }

        $pixCopiaECola = $response['pixCopiaECola'] ?? null;
        $qrcode = null;

        if ($pixCopiaECola) {
            $qrcode = EfiBankClient::generateQrCodeBase64($pixCopiaECola);
        }

        return [
            'pixCopiaECola' => $pixCopiaECola,
            'qrcode' => $qrcode,
            'txid' => $response['txid'] ?? $txid,
        ];
    }

    public function generateValidCpf(): string
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

    public function createBilletCharge(Order $order, array $options = []): OrderPayment
    {
        $tenant = $order->tenant;

        $existingPayment = OrderPayment::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingPayment) {
            return $existingPayment;
        }

        $idempotencyKey = Str::uuid()->toString();
        $amountCents = (int) round(($order->total ?? 0) * 100);

        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'amount_cents' => $amountCents,
            'method' => 'billet',
            'status' => 'processing',
            'idempotency_key' => $idempotencyKey,
            'expires_at' => now()->addDays(3),
        ]);

        try {
            $client = EfiBankClient::forTenant($tenant);

            $body = [
                'items' => [
                    [
                        'name' => "Pedido #{$order->id}",
                        'value' => $amountCents,
                        'amount' => 1,
                    ],
                ],
                'expire_at' => now()->addDays(3)->format('Y-m-d'),
                'payment_types' => ['banking_billet'],
            ];

            $response = $client->createCharge($body);

            $chargeData = $response['data'] ?? $response;
            $barcode = $chargeData['barcode'] ?? ($chargeData['banking_billet']?->barcode ?? null);
            $paymentUrl = $chargeData['link'] ?? ($chargeData['banking_billet']?->link ?? null);
            $chargeId = $chargeData['charge_id'] ?? '';

            $payment->update([
                'status' => 'pending',
                'efi_charge_id' => (string) $chargeId,
                'barcode' => $barcode,
                'payment_url' => $paymentUrl,
                'efi_response_raw' => $response,
            ]);

            return $payment->fresh();
        } catch (\Throwable $e) {
            $payment->update(['status' => 'error']);

            Log::error('Tenant billet charge creation failed', [
                'order_id' => $order->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function verifyPixPayment(string $txid): array
    {
        $tenant = $this->resolveTenantFromTxid($txid);
        $client = EfiBankClient::forTenant($tenant);

        return $client->pixGetCharge($txid);
    }

    public function processTenantWebhook(array $payload, Tenant $tenant): void
    {
        $txid = $payload['pix'][0]['txid'] ?? $payload['txid'] ?? null;

        if (! $txid) {
            Log::warning('Tenant webhook received without txid', ['payload' => $payload]);

            return;
        }

        DB::transaction(function () use ($txid, $tenant) {
            $payment = OrderPayment::where('efi_pix_txid', $txid)
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->isPaid() || ! $payment->isPending()) {
                return;
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'webhook_received_at' => now(),
            ]);

            $payment->order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            Log::info('Tenant payment confirmed via webhook', [
                'order_id' => $payment->order_id,
                'tenant_id' => $tenant->id,
                'txid' => $txid,
                'amount_cents' => $payment->amount_cents,
            ]);
        });
    }

    private function resolveTenantFromTxid(string $txid): Tenant
    {
        $payment = OrderPayment::where('efi_pix_txid', $txid)->firstOrFail();

        return $payment->tenant;
    }
}
