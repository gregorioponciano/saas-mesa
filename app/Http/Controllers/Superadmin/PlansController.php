<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Services\AuditService;
use App\Services\EfiBank\SaasEfiBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PlansController extends Controller
{
    public function __construct(
        private readonly SaasEfiBankService $efiBankService,
        private readonly AuditService $auditService
    ) {}

    public function index(): JsonResponse
    {
        $plans = SaasPlan::orderBy('price_cents')->get();

        return response()->json($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'interval' => ['required', 'string', 'in:month,quarter,semiannual,year'],
            'badge' => ['nullable', 'string', 'max:60'],
            'features_json' => ['nullable'],
            'feature_items' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'border_color' => ['nullable', 'string', 'max:20'],
            'background_color' => ['nullable', 'string', 'max:20'],
        ]);

        if (SaasPlan::where('slug', Str::slug($validated['name']))->exists()) {
            return response()->json(['message' => 'Já existe um plano com este nome.'], 422);
        }

        if (isset($validated['features_json']) && is_string($validated['features_json'])) {
            $validated['features_json'] = json_decode($validated['features_json'], true);
        }

        $plan = SaasPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price_cents' => $validated['price_cents'],
            'interval' => $validated['interval'],
            'badge' => $validated['badge'] ?? null,
            'features_json' => $validated['features_json'] ?? null,
            'feature_items' => $validated['feature_items'] ?? null,
            'border_color' => $validated['border_color'] ?? null,
            'background_color' => $validated['background_color'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        try {
            $this->efiBankService->createPlanOnEfi($plan);
        } catch (\Throwable $e) {
            // Plan created locally even if EfiBank sync fails
        }

        $this->auditService->log(
            'plan.create',
            "Plano \"{$plan->name}\" criado.",
            [
                'plan_id' => $plan->id,
                'price_cents' => $plan->price_cents,
                'interval' => $plan->interval,
            ],
            entityType: SaasPlan::class,
            entityId: (string) $plan->id
        );

        return response()->json($plan, 201);
    }

    public function show(SaasPlan $plan): JsonResponse
    {
        return response()->json($plan);
    }

    public function update(Request $request, SaasPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'interval' => ['sometimes', 'string', 'in:month,quarter,semiannual,year'],
            'badge' => ['nullable', 'string', 'max:60'],
            'features_json' => ['nullable'],
            'feature_items' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'border_color' => ['nullable', 'string', 'max:20'],
            'background_color' => ['nullable', 'string', 'max:20'],
        ]);

        if (isset($validated['name'])) {
            $newSlug = Str::slug($validated['name']);
            $oldSlugFromName = Str::slug($plan->getOriginal('name'));

            if ($newSlug !== $oldSlugFromName) {
                if (SaasPlan::where('slug', $newSlug)->where('id', '!=', $plan->id)->exists()) {
                    return response()->json(['message' => 'Já existe um plano com este nome.'], 422);
                }

                $validated['slug'] = $newSlug;
            }
        }

        if (isset($validated['features_json']) && is_string($validated['features_json'])) {
            $validated['features_json'] = json_decode($validated['features_json'], true);
        }

        $before = [
            'price_cents' => $plan->price_cents,
            'features_json' => $plan->features_json,
            'feature_items' => $plan->feature_items,
        ];

        $plan->update($validated);

        Cache::forget(SaasPlan::planCacheKey($plan->getOriginal('slug')));
        Cache::forget(SaasPlan::planCacheKey($plan->fresh()->slug));

        $this->auditService->log(
            'plan.update',
            "Plano \"{$plan->name}\" atualizado.",
            [
                'plan_id' => $plan->id,
                'before' => $before,
                'after' => [
                    'price_cents' => $plan->fresh()->price_cents,
                    'features_json' => $plan->fresh()->features_json,
                    'feature_items' => $plan->fresh()->feature_items,
                ],
            ],
            entityType: SaasPlan::class,
            entityId: (string) $plan->id
        );

        return response()->json($plan->fresh());
    }

    public function destroy(SaasPlan $plan): JsonResponse
    {
        Cache::forget(SaasPlan::planCacheKey($plan->slug));

        $plan->update(['is_active' => false]);
        $plan->delete();

        $this->auditService->log(
            'plan.delete',
            "Plano \"{$plan->name}\" excluído.",
            ['plan_id' => $plan->id],
            entityType: SaasPlan::class,
            entityId: (string) $plan->id
        );

        return response()->json(null, 204);
    }
}
