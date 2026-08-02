<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEfiBankWebhook;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaasWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        $log = WebhookLog::create([
            'source' => 'saas',
            'tenant_id' => null,
            'payload_json' => $payload,
            'signature' => $request->header('x-efi-hmac-sha256'),
            'is_valid' => true,
            'processed' => false,
        ]);

        ProcessEfiBankWebhook::dispatch($log->id, 'saas', null)
            ->onQueue('webhooks')
            ->delay(now()->addSeconds(2));

        Log::info('Saas webhook queued', [
            'log_id' => $log->id,
        ]);

        return response()->json(['status' => 'queued'], 200);
    }
}
