<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\WebhookLog;
use App\Services\EfiBank\WebhookValidatorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookSignature
{
    public function __construct(
        private readonly WebhookValidatorService $validator
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('x-efi-hmac-sha256');
        $ip = $request->ip();

        $isValid = $this->validator->validate($payload, $signature);

        if (!$isValid) {
            WebhookLog::create([
                'source' => $request->route('tenantId') ? 'tenant' : 'saas',
                'tenant_id' => $request->route('tenantId'),
                'payload_json' => $payload,
                'signature' => $signature,
                'is_valid' => false,
                'processed' => false,
                'error_message' => 'Invalid signature',
            ]);

            Log::warning('Webhook rejected: invalid signature', [
                'ip' => $ip,
                'source' => $request->route('tenantId') ? 'tenant' : 'saas',
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
