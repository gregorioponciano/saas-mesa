<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialController extends Controller
{
    public function payments(Request $request): JsonResponse
    {
        $tenant = request()->get('current_tenant') ?? Auth::user()->tenant;

        $query = OrderPayment::where('tenant_id', $tenant->id)->with('order');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($payments);
    }

    public function summary(Request $request): JsonResponse
    {
        $tenant = request()->get('current_tenant') ?? Auth::user()->tenant;

        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        $totalCollected = (int) OrderPayment::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->sum('amount_cents');

        $todayCollected = (int) OrderPayment::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $today)
            ->sum('amount_cents');

        $thisMonthCollected = (int) OrderPayment::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $thisMonth)
            ->sum('amount_cents');

        $pendingPayments = (int) OrderPayment::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount_cents');

        $totalOrders = Order::where('tenant_id', $tenant->id)->count();
        $paidOrders = Order::where('tenant_id', $tenant->id)
            ->where('payment_status', 'paid')
            ->count();

        $paymentMethods = OrderPayment::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->get()
            ->groupBy('method')
            ->map(fn ($items) => [
                'method' => $items->first()->method,
                'total_cents' => $items->sum('amount_cents'),
                'count' => $items->count(),
            ])
            ->values();

        return response()->json([
            'total_collected_cents' => $totalCollected,
            'total_collected_formatted' => 'R$ '.number_format($totalCollected / 100, 2, ',', '.'),
            'today_collected_cents' => $todayCollected,
            'this_month_collected_cents' => $thisMonthCollected,
            'pending_cents' => $pendingPayments,
            'total_orders' => $totalOrders,
            'paid_orders' => $paidOrders,
            'payment_methods' => $paymentMethods,
        ]);
    }
}
