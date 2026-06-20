<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Services\EfiBank\SaasEfiBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlansController extends Controller
{
    public function __construct(
        private readonly SaasEfiBankService $efiBankService
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
            'interval' => ['required', 'string', 'in:month,year'],
            'features_json' => ['nullable', 'json'],
            'is_active' => ['boolean'],
        ]);

        $plan = SaasPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price_cents' => $validated['price_cents'],
            'interval' => $validated['interval'],
            'features_json' => $validated['features_json'] ? json_decode($validated['features_json'], true) : null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        try {
            $this->efiBankService->createPlanOnEfi($plan);
        } catch (\Throwable $e) {
            // Plan created locally even if EfiBank sync fails
        }

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
            'interval' => ['sometimes', 'string', 'in:month,year'],
            'features_json' => ['nullable', 'json'],
            'is_active' => ['boolean'],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if (isset($validated['features_json']) && is_string($validated['features_json'])) {
            $validated['features_json'] = json_decode($validated['features_json'], true);
        }

        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy(SaasPlan $plan): JsonResponse
    {
        $plan->update(['is_active' => false]);
        $plan->delete();

        return response()->json(null, 204);
    }
}
