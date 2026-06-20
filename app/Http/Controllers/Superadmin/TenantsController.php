<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantsController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function index(): JsonResponse
    {
        $tenants = Tenant::withCount(['users', 'orders', 'tables'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tenant) {
                $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();
                return [
                    'id' => $tenant->id,
                    'uuid' => $tenant->uuid,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'email' => $tenant->email,
                    'plan' => $tenant->plan,
                    'status' => $tenant->status,
                    'users_count' => $tenant->users_count,
                    'orders_count' => $tenant->orders_count,
                    'tables_count' => $tenant->tables_count,
                    'subscription_status' => $subscription?->status,
                    'trial_ends_at' => $subscription?->trial_ends_at,
                    'created_at' => $tenant->created_at,
                ];
            });

        return response()->json($tenants);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['users', 'orders' => function ($q) {
            $q->latest()->limit(10);
        }]);

        $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

        return response()->json([
            'tenant' => $tenant,
            'subscription' => $subscription,
            'stats' => [
                'total_orders' => $tenant->orders()->count(),
                'total_users' => $tenant->users()->count(),
                'total_tables' => $tenant->tables()->count(),
            ],
        ]);
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $this->subscriptionService->suspendTenant($tenant, 'manual_suspension');

        return response()->json([
            'message' => 'Tenant suspenso com sucesso.',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function reactivate(Tenant $tenant): JsonResponse
    {
        $this->subscriptionService->reactivateTenant($tenant);

        return response()->json([
            'message' => 'Tenant reativado com sucesso.',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function changePlan(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:saas_plans,id'],
        ]);

        $plan = SaasPlan::findOrFail($validated['plan_id']);

        $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

        if ($subscription) {
            $subscription->update([
                'plan_id' => $plan->id,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'plan_changed_at' => now()->toIso8601String(),
                    'previous_plan_id' => $subscription->plan_id,
                ]),
            ]);
        }

        $tenant->update([
            'plan' => $plan->slug === 'premium' ? 'paid' : 'free',
            'max_tables' => $plan->features_json['max_tables'] ?? 10,
        ]);

        return response()->json([
            'message' => 'Plano alterado com sucesso.',
            'tenant_id' => $tenant->id,
            'plan' => $plan->name,
        ]);
    }

    public function forceCharge(Tenant $tenant): JsonResponse
    {
        $subscription = SaasSubscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'past_due'])
            ->first();

        if (!$subscription) {
            return response()->json(['error' => 'Tenant sem assinatura ativa.'], 400);
        }

        // Dispatch job to create charge
        \App\Jobs\CreateTenantSubscription::dispatch($tenant, $subscription->plan);

        return response()->json([
            'message' => 'Cobrança forçada enviada para processamento.',
            'subscription_id' => $subscription->id,
        ]);
    }
}
