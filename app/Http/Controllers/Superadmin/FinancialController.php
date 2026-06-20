<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPaymentHistory;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $activeTenants = Tenant::whereIn('status', ['active', 'trial'])->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $trialTenants = Tenant::where('status', 'trial')->count();

        $mrr = SaasSubscription::whereIn('status', ['active', 'trial'])
            ->with('plan')
            ->get()
            ->sum(fn ($s) => $s->plan?->price_cents ?? 0);

        $totalCollected = SaasPaymentHistory::where('status', 'paid')->sum('amount_cents');

        $pendingThisWeek = SaasSubscription::whereIn('status', ['active', 'past_due'])
            ->where('next_billing_date', '<=', now()->addDays(7))
            ->count();

        $failedWebhooksLast24h = \App\Models\WebhookLog::where('created_at', '>=', now()->subDay())
            ->where('is_valid', false)
            ->count();

        $revenueLast12Months = SaasPaymentHistory::where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->get()
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'))
            ->map(fn ($items) => [
                'month' => $items->first()->paid_at->format('Y-m'),
                'total_cents' => $items->sum('amount_cents'),
                'count' => $items->count(),
            ])
            ->values();

        return response()->json([
            'stats' => [
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'trial_tenants' => $trialTenants,
                'mrr_cents' => $mrr,
                'mrr_formatted' => 'R$ ' . number_format($mrr / 100, 2, ',', '.'),
                'total_collected_cents' => $totalCollected,
                'pending_renewals_7days' => $pendingThisWeek,
                'failed_webhooks_24h' => $failedWebhooksLast24h,
            ],
            'revenue_last_12_months' => $revenueLast12Months,
        ]);
    }

    public function tenant(Tenant $tenant, Request $request): JsonResponse
    {
        $payments = SaasPaymentHistory::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->plan,
                'status' => $tenant->status,
            ],
            'subscription' => $subscription,
            'payments' => $payments,
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $query = SaasPaymentHistory::with(['subscription', 'tenant']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($payments);
    }
}
