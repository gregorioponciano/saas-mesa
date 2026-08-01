<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    public function __construct(
        private readonly GeocodingService $geocodingService,
        private readonly AuditService $auditService
    ) {}

    public function show(Tenant $tenant): JsonResponse
    {
        $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'slug' => $tenant->slug,
                'whatsapp' => $tenant->whatsapp,
                'opening_time' => $tenant->opening_time ? substr((string) $tenant->opening_time, 0, 5) : null,
                'closing_time' => $tenant->closing_time ? substr((string) $tenant->closing_time, 0, 5) : null,
                'delivery_cost_enabled' => (bool) $tenant->delivery_cost_enabled,
                'delivery_cost_per_order' => (float) $tenant->delivery_cost_per_order,
                'delivery_cost_per_km' => (float) $tenant->delivery_cost_per_km,
                'address' => $tenant->address,
                'number' => $tenant->number,
                'neighborhood' => $tenant->neighborhood,
                'city' => $tenant->city,
                'state' => $tenant->state,
                'zipcode' => $tenant->zipcode,
                'delivery_radius' => (float) $tenant->delivery_radius,
                'coupons_enabled' => (bool) $tenant->coupons_enabled,
                'points_enabled' => (bool) ($tenant->loyaltyConfig?->points_enabled ?? false),
                'logo_url' => $tenant->logoUrl(),
                'plan' => $tenant->plan,
                'plan_label' => $tenant->planLabel(),
                'status' => $tenant->status,
                'subscription_status' => $subscription?->status,
                'created_at' => $tenant->created_at,
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'opening_time' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'closing_time' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'delivery_cost_enabled' => ['boolean'],
            'delivery_cost_per_order' => ['numeric', 'min:0'],
            'delivery_cost_per_km' => ['numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'zipcode' => ['nullable', 'string', 'max:10'],
            'delivery_radius' => ['numeric', 'min:0', 'max:200'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'] ?? null,
            'opening_time' => isset($validated['opening_time']) && $validated['opening_time']
                ? \DateTime::createFromFormat('H:i', $validated['opening_time'])->format('H:i:s')
                : null,
            'closing_time' => isset($validated['closing_time']) && $validated['closing_time']
                ? \DateTime::createFromFormat('H:i', $validated['closing_time'])->format('H:i:s')
                : null,
            'delivery_cost_enabled' => (bool) ($validated['delivery_cost_enabled'] ?? false),
            'delivery_cost_per_order' => (float) ($validated['delivery_cost_per_order'] ?? 0),
            'delivery_cost_per_km' => (float) ($validated['delivery_cost_per_km'] ?? 0),
            'address' => $validated['address'] ?? null,
            'number' => $validated['number'] ?? null,
            'neighborhood' => $validated['neighborhood'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zipcode' => $validated['zipcode'] ?? null,
            'delivery_radius' => (float) ($validated['delivery_radius'] ?? 10),
        ];

        if ($data['address'] && $data['city']) {
            try {
                $coords = $this->geocodingService->geocode(
                    $data['address'].', '.($data['number'] ? $data['number'].', ' : '').$data['neighborhood'],
                    $data['city'],
                    $data['state'],
                    $data['zipcode']
                );

                if ($coords) {
                    $data['latitude'] = $coords['lat'];
                    $data['longitude'] = $coords['lng'];
                }
            } catch (\Throwable) {
                // Geocoding é opcional — atualiza mesmo sem coordenadas.
            }
        }

        $tenant->update($data);

        $this->auditService->log(
            'tenant.update_settings',
            "Configurações da empresa \"{$tenant->name}\" atualizadas.",
            [
                'tenant_id' => $tenant->id,
                'changed_fields' => array_keys($data),
                'geocoded' => isset($data['latitude']),
            ],
            $tenant,
            Tenant::class,
            (string) $tenant->id
        );

        return response()->json([
            'message' => 'Configurações atualizadas com sucesso.',
            'tenant' => $this->show($tenant)->getData(true)['tenant'],
        ]);
    }
}
