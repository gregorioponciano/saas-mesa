<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrderPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(
        private readonly int $paymentId
    ) {}

    public function handle(): void
    {
        $payment = OrderPayment::find($this->paymentId);

        if (!$payment || $payment->isPaid()) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $order = $payment->order;
            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

                OrderPaid::dispatch($order);
            }

            Log::info('Payment status updated', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount_cents' => $payment->amount_cents,
            ]);
        });
    }
}
