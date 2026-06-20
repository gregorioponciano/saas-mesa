<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantEfiCredentials;
use App\Services\EfiBank\TenantEfiCredentialsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EfiCredentialsController extends Controller
{
    public function __construct(
        private readonly TenantEfiCredentialsService $credentialsService
    ) {}

    public function show(): JsonResponse
    {
        $tenant = request()->get('current_tenant') ?? Auth::user()->tenant;
        $data = $this->credentialsService->show($tenant);

        if (!$data['configured']) {
            return response()->json([
                'configured' => false,
                'message' => 'Credenciais EfiBank não configuradas.',
            ]);
        }

        return response()->json([
            'configured' => true,
            'account_type' => $data['account_type'],
            'is_active' => $data['is_active'],
            'has_pix_key' => $data['has_pix_key'],
            'has_certificate' => $data['has_certificate'],
            'client_id_masked' => $data['client_id_masked'],
            'pix_key_masked' => $data['pix_key_masked'],
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
        ]);

        $this->credentialsService->save($tenant, [
            'client_id' => $validated['client_id'],
            'client_secret' => $validated['client_secret'],
            'pix_key' => $validated['pix_key'] ?? '',
            'account_type' => $validated['account_type'],
        ], $validated['certificate_content'] ?? null);

        return response()->json([
            'message' => 'Credenciais EfiBank atualizadas com sucesso.',
            'account_type' => $validated['account_type'],
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

        $result = $this->credentialsService->test($tenant);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }
}
