<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tenant;
use App\Services\PointsService;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    public function updated(Tenant $tenant): void
    {
        if ($tenant->isDirty('plan')) {
            $originalPlan = $tenant->getOriginal('plan');
            $newPlan = $tenant->plan;

            if ($originalPlan === Tenant::PLAN_PAID && $newPlan !== Tenant::PLAN_PAID) {
                try {
                    app(PointsService::class)->disableForTenant($tenant);
                } catch (\Throwable $e) {
                    Log::error('Erro ao desativar pontos no downgrade', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
