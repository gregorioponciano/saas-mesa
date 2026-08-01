<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookLogsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebhookLog::orderByDesc('created_at');

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('valid')) {
            $query->where('is_valid', filter_var($request->valid, FILTER_VALIDATE_BOOL));
        }

        if ($request->filled('processed')) {
            $query->where('processed', filter_var($request->processed, FILTER_VALIDATE_BOOL));
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        if ($request->filled('has_error')) {
            $query->whereNotNull('error_message');
        }

        $logs = $query->with('tenant')
            ->paginate($request->get('per_page', 20))
            ->through(fn (WebhookLog $log) => [
                'id' => $log->id,
                'source' => $log->source,
                'tenant_id' => $log->tenant_id,
                'tenant_name' => $log->tenant?->name,
                'is_valid' => (bool) $log->is_valid,
                'processed' => (bool) $log->processed,
                'error_message' => $log->error_message,
                'payload_preview' => $this->preview($log->payload_json),
                'created_at' => $log->created_at,
            ]);

        $stats = [
            'total' => WebhookLog::count(),
            'invalid' => WebhookLog::where('is_valid', false)->count(),
            'errors' => WebhookLog::whereNotNull('error_message')->count(),
            'last_24h' => WebhookLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        return response()->json([
            'logs' => $logs,
            'stats' => $stats,
        ]);
    }

    public function show(WebhookLog $log): JsonResponse
    {
        return response()->json($log);
    }

    private function preview(?string $payload): ?string
    {
        if (! $payload) {
            return null;
        }

        $decoded = json_decode($payload, true);

        return json_encode(
            $decoded,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
