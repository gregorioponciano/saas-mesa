<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\TenantEfiCredentials;
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
        $tenantId = $request->route('tenantId');

        $secret = null;

        if ($tenantId !== null) {
            $secret = $this->resolveTenantWebhookSecret((int) $tenantId);

            if ($secret === null) {
                Log::warning('Webhook rejected: tenant webhook secret not configured', [
                    'ip' => $ip,
                    'tenant_id' => (int) $tenantId,
                    'source' => 'tenant',
                ]);

                WebhookLog::create([
                    'source' => 'tenant',
                    'tenant_id' => (int) $tenantId,
                    'payload_json' => $payload,
                    'signature' => $signature,
                    'is_valid' => false,
                    'processed' => false,
                    'error_message' => 'Tenant webhook secret not configured',
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $isValid = $this->validator->validate($payload, $signature, $secret);

        if (!$isValid) {
            WebhookLog::create([
                'source' => $tenantId !== null ? 'tenant' : 'saas',
                'tenant_id' => $tenantId !== null ? (int) $tenantId : null,
                'payload_json' => $payload,
                'signature' => $signature,
                'is_valid' => false,
                'processed' => false,
                'error_message' => 'Invalid signature',
            ]);

            Log::warning('Webhook rejected: invalid signature', [
                'ip' => $ip,
                'source' => $tenantId !== null ? 'tenant' : 'saas',
                'tenant_id' => $tenantId !== null ? (int) $tenantId : null,
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    private function resolveTenantWebhookSecret(int $tenantId): ?string
    {
        $credentials = TenantEfiCredentials::where('tenant_id', $tenantId)->first();

        if (!$credentials || empty($credentials->webhook_secret_encrypted)) {
            return null;
        }

        return $credentials->decryptWebhookSecret();
    }
}
