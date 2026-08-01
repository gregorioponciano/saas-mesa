<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Jobs\CreateTenantSubscription;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LgpdService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantsController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly AuditService $auditService,
        private readonly LgpdService $lgpdService
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:tenants,email'],
            'whatsapp' => ['nullable', 'string', 'max:25'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_password' => ['required', 'string', 'min:8'],
            'plan_id' => ['nullable', 'exists:saas_plans,id'],
        ]);

        $plan = null;

        if (! empty($validated['plan_id'])) {
            $plan = SaasPlan::findOrFail($validated['plan_id']);
        } else {
            $plan = SaasPlan::firstOrCreate(['slug' => 'free'], [
                'name' => 'Gratuito',
                'price_cents' => 0,
                'interval' => 'month',
                'features_json' => [
                    'max_tables' => 2,
                    'max_products' => 20,
                    'max_users' => 2,
                    'pix_payments' => true,
                    'boleto_payments' => false,
                    'reports' => false,
                    'delivery' => false,
                    'priority_support' => false,
                    'backup_retention_days' => 7,
                    'backup_max_count' => 3,
                ],
                'is_active' => true,
            ]);
        }

        $baseSlug = Str::slug($validated['name']) ?: 'empresa';
        $slug = $baseSlug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.($counter++);
        }

        [$tenant, $admin] = DB::transaction(function () use ($validated, $plan, $slug) {
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'whatsapp' => $validated['whatsapp'],
                'slug' => $slug,
                'plan' => $plan && $plan->slug === 'premium' ? 'paid' : 'free',
                'max_tables' => $plan?->features_json['max_tables'] ?? Tenant::PLAN_MAX_TABLES['free'],
                'status' => 'trial',
            ]);

            $admin = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['email'],
                'password' => $validated['admin_password'],
                'tenant_id' => $tenant->id,
                'role' => 'admin',
            ]);

            SaasSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan?->id,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(7),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'next_billing_date' => now()->addDays(7),
            ]);

            return [$tenant, $admin];
        });

        $this->auditService->log(
            'tenant.create',
            "Empresa \"{$tenant->name}\" criada.",
            [
                'tenant_id' => $tenant->id,
                'plan' => $plan?->name ?? 'Gratuito',
                'admin_email' => LgpdService::anonymizeEmail($validated['email']),
            ],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

        return response()->json([
            'message' => 'Empresa criada com sucesso.',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'plan' => $tenant->plan,
                'status' => $tenant->status,
                'admin_user_id' => $admin->id,
            ],
        ], 201);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $name = $tenant->name;

        $this->lgpdService->anonymizeTenant($tenant);

        $this->auditService->log(
            'tenant.anonymize',
            "Empresa \"{$name}\" anonimizada e encerrada (LGPD).",
            ['tenant_id' => $tenant->id],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

        return response()->json([
            'message' => 'Empresa anonimizada e encerrada com sucesso (LGPD).',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function export(Tenant $tenant): JsonResponse
    {
        $data = $this->lgpdService->exportTenantData($tenant);

        $this->auditService->log(
            'tenant.export_data',
            "Exportação LGPD da empresa \"{$tenant->name}\".",
            ['tenant_id' => $tenant->id],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

        $filename = 'lgpd-'.Str::slug($tenant->name).'-'.now()->format('Y-m-d').'.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $this->subscriptionService->suspendTenant($tenant, 'manual_suspension');

        $this->auditService->log(
            'tenant.suspend',
            "Empresa \"{$tenant->name}\" suspensa.",
            ['tenant_name' => $tenant->name],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

        return response()->json([
            'message' => 'Tenant suspenso com sucesso.',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function reactivate(Tenant $tenant): JsonResponse
    {
        $this->subscriptionService->reactivateTenant($tenant);

        $this->auditService->log(
            'tenant.reactivate',
            "Empresa \"{$tenant->name}\" reativada.",
            ['tenant_name' => $tenant->name],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

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

        $previousPlan = $tenant->plan;

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

        $this->auditService->log(
            'tenant.change_plan',
            "Plano da empresa \"{$tenant->name}\" alterado para {$plan->name}.",
            [
                'previous_plan' => $previousPlan,
                'new_plan_id' => $plan->id,
                'new_plan' => $plan->name,
            ],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

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

        if (! $subscription) {
            return response()->json(['error' => 'Tenant sem assinatura ativa.'], 400);
        }

        // Dispatch job to create charge
        CreateTenantSubscription::dispatch($tenant, $subscription->plan);

        $this->auditService->log(
            'tenant.force_charge',
            "Cobrança forçada disparada para \"{$tenant->name}\".",
            ['subscription_id' => $subscription->id],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

        return response()->json([
            'message' => 'Cobrança forçada enviada para processamento.',
            'subscription_id' => $subscription->id,
        ]);
    }
}
