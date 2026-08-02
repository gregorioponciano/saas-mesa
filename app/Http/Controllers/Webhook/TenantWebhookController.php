<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEfiBankWebhook;
use App\Models\Tenant;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantWebhookController extends Controller
{
    public function handle(Request $request, string $tenantId): JsonResponse
    {
        $payload = $request->getContent();

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            Log::warning('Tenant webhook received for unknown tenant', [
                'tenant_id' => $tenantId,
            ]);

            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $log = WebhookLog::create([
            'source' => 'tenant',
            'tenant_id' => (int) $tenantId,
            'payload_json' => $payload,
            'signature' => $request->header('x-efi-hmac-sha256'),
            'is_valid' => true,
            'processed' => false,
        ]);

        ProcessEfiBankWebhook::dispatch($log->id, 'tenant', (int) $tenantId)
            ->onQueue('webhooks')
            ->delay(now()->addSeconds(2));

        Log::info('Tenant webhook queued', [
            'log_id' => $log->id,
            'tenant_id' => $tenantId,
        ]);

        return response()->json(['status' => 'queued'], 200);
    }
}
