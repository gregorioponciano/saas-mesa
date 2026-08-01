<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('admin:id,name,email')->orderByDesc('created_at');

        if ($request->filled('admin_user_id')) {
            $query->where('admin_user_id', $request->integer('admin_user_id'));
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->action.'%');
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from.' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $logs = $query->paginate($request->get('per_page', 25))
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'admin_name' => $log->admin?->name ?? 'Sistema',
                'admin_email' => $log->admin?->email,
                'tenant_id' => $log->tenant_id,
                'action' => $log->action,
                'description' => $log->description,
                'data' => $log->data,
                'ip' => $log->ip,
                'created_at' => $log->created_at,
            ]);

        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return response()->json([
            'logs' => $logs,
            'actions' => $actions,
        ]);
    }
}
