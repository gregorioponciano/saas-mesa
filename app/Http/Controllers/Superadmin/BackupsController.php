<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Services\AuditService;
use App\Services\TenantBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupsController extends Controller
{
    public function __construct(
        private readonly TenantBackupService $backupService,
        private readonly AuditService $auditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = TenantBackup::with('tenant')->orderByDesc('created_at');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        $backups = $query->paginate($request->get('per_page', 20));

        $stats = [
            'total_backups' => TenantBackup::count(),
            'total_size_bytes' => (int) TenantBackup::sum('size_bytes'),
            'expired_count' => TenantBackup::where('expires_at', '<=', now())->count(),
        ];

        return response()->json([
            'backups' => $backups,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $backup = $this->backupService->createBackup($tenant);

        $this->auditService->log(
            'backup.create',
            "Backup manual criado para \"{$tenant->name}\".",
            ['backup_id' => $backup->id, 'size_bytes' => $backup->size_bytes],
            $tenant,
            TenantBackup::class,
            (string) $backup->id
        );

        return response()->json([
            'backup' => $backup->load('tenant'),
            'message' => 'Backup criado com sucesso.',
        ], 201);
    }

    public function destroy(TenantBackup $backup): JsonResponse
    {
        try {
            $tenantName = $backup->tenant?->name ?? 'desconhecida';
            $this->backupService->deleteBackup($backup);

            $this->auditService->log(
                'backup.delete',
                "Backup de \"{$tenantName}\" excluído.",
                ['backup_id' => $backup->id],
                $backup->tenant,
                TenantBackup::class,
                (string) $backup->id
            );

            return response()->json(['message' => 'Backup excluído.']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Falha ao excluir o backup.'], 500);
        }
    }
}
