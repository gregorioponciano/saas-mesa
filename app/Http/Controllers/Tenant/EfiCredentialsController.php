<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantEfiCredentials;
use App\Services\EfiBank\EfiBankClient;
use App\Services\EncryptedCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EfiCredentialsController extends Controller
{
    public function __construct(
        private readonly EncryptedCredentialService $encryptionService
    ) {}

    public function show(): JsonResponse
    {
        $tenant = request()->get('current_tenant') ?? Auth::user()->tenant;

        $credentials = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();

        if (!$credentials) {
            return response()->json([
                'configured' => false,
                'message' => 'Credenciais EfiBank não configuradas.',
            ]);
        }

        return response()->json([
            'configured' => true,
            'account_type' => $credentials->account_type,
            'is_active' => $credentials->is_active,
            'has_pix_key' => !empty($credentials->pix_key_encrypted),
            'has_certificate' => !empty($credentials->certificate_content_encrypted),
            'client_id_masked' => $this->maskString($credentials->decryptClientId()),
            'pix_key_masked' => $credentials->pix_key_encrypted
                ? $this->maskString($credentials->decryptPixKey() ?? '')
                : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = request()->get('current_tenant') ?? Auth::user()->tenant;

        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'pix_key' => ['nullable', 'string'],
            'account_type' => ['required', 'string', 'in:sandbox,production'],
            'certificate_content' => ['nullable', 'string'],
            'certificate_path' => ['nullable', 'string'],
        ]);

        $encrypted = $this->encryptionService->encryptTenantCredentials([
            'client_id' => $validated['client_id'],
            'client_secret' => $validated['client_secret'],
            'pix_key' => $validated['pix_key'] ?? '',
            'certificate_content' => $validated['certificate_content'] ?? '',
        ]);

        $credentials = TenantEfiCredentials::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'client_id_encrypted' => $encrypted['client_id_encrypted'],
                'client_secret_encrypted' => $encrypted['client_secret_encrypted'],
                'pix_key_encrypted' => $encrypted['pix_key_encrypted'],
                'certificate_content_encrypted' => $encrypted['certificate_content_encrypted'],
                'account_type' => $validated['account_type'],
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Credenciais EfiBank atualizadas com sucesso.',
            'account_type' => $credentials->account_type,
        ]);
    }

    public function test(): JsonResponse
    {
        $tenant = request()->get('current_tenant') ?? Auth::user()->tenant;

        $credentials = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();

        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais não configuradas.',
            ], 400);
        }

        try {
            $client = EfiBankClient::forTenant($tenant);
            $token = $client->getAccessToken();

            return response()->json([
                'success' => true,
                'message' => 'Conexão com EfiBank estabelecida com sucesso.',
                'account_type' => $credentials->account_type,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha na conexão: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function maskString(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 4) . str_repeat('*', $len - 4);
    }
}
