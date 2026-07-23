<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeliveryAcceptInviteRequest;
use App\Models\DeliveryPerson;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class DeliveryInvitationController extends Controller
{
    public function __construct(
        private readonly DeliveryService $deliveryService
    ) {}

    public function show(string $token): JsonResponse
    {
        $delivery = DeliveryPerson::withoutTenant()
            ->where('invite_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$delivery || !$delivery->hasValidInvite()) {
            return response()->json(['message' => 'Convite inválido ou expirado'], 404);
        }

        return response()->json([
            'name' => $delivery->name,
            'phone' => $delivery->phone,
            'invite_expires_at' => $delivery->invite_expires_at->toIso8601String(),
        ]);
    }

    public function accept(string $token, DeliveryAcceptInviteRequest $request): JsonResponse
    {
        $delivery = DeliveryPerson::withoutTenant()
            ->where('invite_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$delivery || !$delivery->hasValidInvite()) {
            return response()->json(['message' => 'Convite inválido ou expirado'], 404);
        }

        $data = [
            'password' => Hash::make($request->password),
            'invite_token' => null,
            'invite_expires_at' => null,
            'activated_at' => now(),
        ];

        if ($request->filled('cpf')) {
            $data['cpf'] = $request->cpf;
        }
        if ($request->filled('cnh')) {
            $data['cnh'] = $request->cnh;
        }
        if ($request->filled('vehicle_plate')) {
            $data['vehicle_plate'] = $request->vehicle_plate;
        }
        if ($request->filled('vehicle_model')) {
            $data['vehicle_model'] = $request->vehicle_model;
        }

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $this->deliveryService->uploadAvatar(
                $request->file('avatar'),
                $delivery->tenant_id
            );
        }

        $delivery->update($data);

        $token = $this->deliveryService->createToken($delivery);

        return response()->json([
            'message' => 'Convite aceito! Conta ativada.',
            'token' => $token->plainTextToken,
            'name' => $delivery->name,
            'id' => $delivery->id,
        ]);
    }
}
