<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPaymentHistory;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Models\TenantInvoice;
use App\Models\WebhookLog;
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

        $failedWebhooksLast24h = WebhookLog::where('created_at', '>=', now()->subDay())
            ->where('is_valid', false)
            ->count();

        $activeSubscriptions = SaasSubscription::whereIn('status', ['active', 'trial'])->count();
        $paidTenants = Tenant::where('plan', 'paid')->whereIn('status', ['active', 'trial'])->count();
        $totalBackups = TenantBackup::count();
        $backupsSizeBytes = (int) TenantBackup::sum('size_bytes');

        $recentTenants = Tenant::withCount(['users', 'orders', 'tables'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'plan' => $tenant->plan,
                'status' => $tenant->status,
                'users_count' => $tenant->users_count,
                'orders_count' => $tenant->orders_count,
                'created_at' => $tenant->created_at,
            ]);

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
                'paid_tenants' => $paidTenants,
                'active_subscriptions' => $activeSubscriptions,
                'mrr_cents' => $mrr,
                'mrr_formatted' => 'R$ '.number_format($mrr / 100, 2, ',', '.'),
                'total_collected_cents' => $totalCollected,
                'pending_renewals_7days' => $pendingThisWeek,
                'failed_webhooks_24h' => $failedWebhooksLast24h,
                'total_backups' => $totalBackups,
                'backups_size_bytes' => $backupsSizeBytes,
            ],
            'recent_tenants' => $recentTenants,
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

    public function subscriptions(Request $request): JsonResponse
    {
        $query = SaasSubscription::with(['tenant', 'plan'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        $subscriptions = $query->paginate($request->get('per_page', 15))
            ->through(fn (SaasSubscription $sub) => [
                'id' => $sub->id,
                'tenant_id' => $sub->tenant_id,
                'tenant_name' => $sub->tenant?->name,
                'plan_name' => $sub->plan?->name,
                'price_cents' => $sub->plan?->price_cents,
                'status' => $sub->status,
                'payment_method' => $sub->payment_method,
                'trial_ends_at' => $sub->trial_ends_at,
                'current_period_end' => $sub->current_period_end,
                'next_billing_date' => $sub->next_billing_date,
                'suspended_at' => $sub->suspended_at,
                'cancelled_at' => $sub->cancelled_at,
                'created_at' => $sub->created_at,
            ]);

        $stats = [
            'active' => SaasSubscription::whereIn('status', ['active', 'trial'])->count(),
            'past_due' => SaasSubscription::where('status', 'past_due')->count(),
            'suspended' => SaasSubscription::where('status', 'suspended')->count(),
            'cancelled' => SaasSubscription::where('status', 'cancelled')->count(),
            'mrr_cents' => SaasSubscription::whereIn('status', ['active', 'trial'])
                ->with('plan')
                ->get()
                ->sum(fn ($s) => $s->plan?->price_cents ?? 0),
        ];

        return response()->json([
            'subscriptions' => $subscriptions,
            'stats' => $stats,
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $query = TenantInvoice::with('tenant')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        $invoices = $query->paginate($request->get('per_page', 15))
            ->through(fn (TenantInvoice $invoice) => [
                'id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
                'tenant_name' => $invoice->tenant?->name,
                'amount_cents' => $invoice->amount_cents,
                'status' => $invoice->status,
                'period_start' => $invoice->period_start,
                'period_end' => $invoice->period_end,
                'paid_at' => $invoice->paid_at,
                'items_json' => $invoice->items_json,
                'created_at' => $invoice->created_at,
            ]);

        $stats = [
            'total' => TenantInvoice::count(),
            'open_cents' => TenantInvoice::where('status', '!=', 'paid')->sum('amount_cents'),
            'collected_cents' => TenantInvoice::where('status', 'paid')->sum('amount_cents'),
        ];

        return response()->json([
            'invoices' => $invoices,
            'stats' => $stats,
        ]);
    }
}
