<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Registra uma ação administrativa para fins de auditoria e conformidade (LGPD).
     */
    public function log(
        string $action,
        ?string $description = null,
        array $data = [],
        ?Tenant $tenant = null,
        ?string $entityType = null,
        ?string $entityId = null
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'admin_user_id' => Auth::id(),
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'data' => $data,
            'ip' => $request?->ip(),
        ]);
    }
}
