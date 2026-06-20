<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\EfiBank\TenantEfiBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        private readonly TenantEfiBankService $efiBankService
    ) {}

    public function initiate(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $this->ensureTenantAccess($order);

        $validated = $request->validate([
            'method' => ['sometimes', 'string', 'in:pix,billet'],
            'payer_name' => ['sometimes', 'string', 'max:255'],
            'payer_cpf' => ['sometimes', 'string', 'max:14'],
        ]);

        try {
            $method = $validated['method'] ?? 'pix';

            $payment = match ($method) {
                'billet' => $this->efiBankService->createBilletCharge($order, $validated),
                default => $this->efiBankService->createPixCharge($order, $validated),
            };

            return response()->json([
                'payment' => [
                    'id' => $payment->id,
                    'amount_cents' => $payment->amount_cents,
                    'amount_formatted' => 'R$ ' . number_format($payment->amount_cents / 100, 2, ',', '.'),
                    'method' => $payment->method,
                    'status' => $payment->status,
                    'qrcode' => $payment->qrcode,
                    'qrcode_image' => $payment->qrcode_image,
                    'barcode' => $payment->barcode,
                    'payment_url' => $payment->payment_url,
                    'expires_at' => $payment->expires_at,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'payment_failed',
                'message' => 'Falha ao gerar cobrança. Tente novamente.',
            ], 422);
        }
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $this->ensureTenantAccess($order);

        $payment = OrderPayment::where('order_id', $order->id)
            ->latest()
            ->first();

        if (!$payment) {
            return response()->json([
                'error' => 'no_payment',
                'message' => 'Nenhum pagamento encontrado para este pedido.',
            ], 404);
        }

        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'method' => $payment->method,
                'amount_cents' => $payment->amount_cents,
                'paid_at' => $payment->paid_at,
                'expires_at' => $payment->expires_at,
            ],
            'order' => [
                'id' => $order->id,
                'payment_status' => $order->payment_status,
                'total' => $order->total,
            ],
        ]);
    }

    public function qrcode(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $this->ensureTenantAccess($order);

        $payment = OrderPayment::where('order_id', $order->id)
            ->where('method', 'pix')
            ->whereNotNull('qrcode')
            ->latest()
            ->first();

        if (!$payment) {
            return response()->json([
                'error' => 'no_qrcode',
                'message' => 'Nenhum QR Code disponível para este pedido.',
            ], 404);
        }

        return response()->json([
            'qrcode' => $payment->qrcode,
            'qrcode_image' => $payment->qrcode_image,
            'expires_at' => $payment->expires_at,
            'amount_cents' => $payment->amount_cents,
        ]);
    }

    private function ensureTenantAccess(Order $order): void
    {
        $tenant = request()->get('current_tenant') ?? Auth::user()->tenant;

        if ($order->tenant_id !== $tenant->id) {
            abort(403, 'Acesso negado.');
        }
    }
}
