<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyConfig;
use App\Models\Tenant;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoyaltyController extends Controller
{
    public function __construct(
        private readonly PointsService $pointsService
    ) {}

    public function index(): JsonResponse
    {
        $tenants = Tenant::with('loyaltyConfig')
            ->orderBy('name')
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'plan' => $tenant->plan,
                    'plan_label' => $tenant->planLabel(),
                    'points_enabled' => $tenant->relationLoaded('loyaltyConfig')
                        ? ($tenant->loyaltyConfig?->points_enabled ?? false)
                        : false,
                    'status' => $tenant->status,
                ];
            });

        return response()->json($tenants);
    }

    public function toggle(Tenant $tenant): JsonResponse
    {
        $config = LoyaltyConfig::forTenant($tenant);

        $newState = !$config->points_enabled;

        if ($newState && !$tenant->isPaid()) {
            return response()->json([
                'error' => 'Nao e possivel ativar pontos para um tenant que nao esta no plano Premium.',
            ], 422);
        }

        $config->update(['points_enabled' => $newState]);

        Log::info('Superadmin forced loyalty toggle', [
            'tenant_id' => $tenant->id,
            'new_state' => $newState,
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'tenant_id' => $tenant->id,
            'points_enabled' => $newState,
            'message' => $newState ? 'Pontos ativados' : 'Pontos desativados',
        ]);
    }
}
