<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeliveryLoginRequest;
use App\Http\Requests\Api\DeliveryUpdateStatusRequest;
use App\Models\DeliveryPerson;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class DeliveryController extends Controller
{
    public function __construct(
        private readonly DeliveryService $deliveryService
    ) {}

    public function login(DeliveryLoginRequest $request): JsonResponse
    {
        $delivery = $this->deliveryService->login(
            $request->phone,
            $request->password
        );

        if (!$delivery) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $token = $this->deliveryService->createToken($delivery);

        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $delivery->name,
            'phone' => $delivery->phone,
            'id' => $delivery->id,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $this->deliveryService->logout($delivery);

        return response()->json(['message' => 'Deslogado com sucesso']);
    }

    public function orders(Request $request): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $orders = $this->deliveryService->getAvailableOrders($delivery);

        return response()->json(['orders' => $orders]);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $orders = $this->deliveryService->getMyOrders($delivery);

        return response()->json(['orders' => $orders]);
    }

    public function acceptOrder(Request $request, int $orderId): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $order = $this->deliveryService->acceptOrder($delivery, $orderId);

        if (!$order) {
            return response()->json(['message' => 'Pedido não encontrado ou já foi aceito'], 404);
        }

        return response()->json([
            'message' => 'Pedido aceito com sucesso. Status: Coletado.',
            'order_id' => $order->id,
            'status' => $order->status,
        ]);
    }

    public function refuseOrder(Request $request, int $orderId): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $refused = $this->deliveryService->refuseOrder($delivery, $orderId);

        if (!$refused) {
            return response()->json(['message' => 'Pedido não encontrado ou não está disponível'], 404);
        }

        return response()->json(['message' => 'Pedido recusado']);
    }

    public function pickupOrder(Request $request, int $orderId): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $order = $this->deliveryService->markPickedUp($delivery, $orderId);

        if (!$order) {
            return response()->json(['message' => 'Pedido não encontrado ou não está no status coletado'], 404);
        }

        return response()->json([
            'message' => 'Pedido saiu para entrega',
            'order_id' => $order->id,
            'status' => $order->status,
        ]);
    }

    public function updateStatus(DeliveryUpdateStatusRequest $request, int $orderId): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $status = $request->status;

        if ($status === 'entregue') {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $this->deliveryService->uploadDeliveryPhoto(
                    $request->file('photo'),
                    $delivery->tenant_id,
                    $delivery->id
                );
            }

            $order = $this->deliveryService->markDelivered(
                $delivery,
                $orderId,
                $photoPath,
                $request->float('lat'),
                $request->float('lng')
            );

            if (!$order) {
                return response()->json(['message' => 'Pedido não encontrado ou não está em rota de entrega'], 404);
            }

            return response()->json([
                'message' => 'Entrega confirmada',
                'status' => $order->status,
            ]);
        }

        if ($status === 'cancelado') {
            $order = $this->deliveryService->cancelOrder($delivery, $orderId);

            if (!$order) {
                return response()->json(['message' => 'Pedido não encontrado ou já foi finalizado'], 404);
            }

            return response()->json([
                'message' => 'Pedido cancelado',
                'status' => $order->status,
            ]);
        }

        return response()->json(['message' => 'Status inválido'], 422);
    }

    public function profile(Request $request): JsonResponse
    {
        $delivery = $this->getDeliveryPerson($request);
        if (!$delivery) {
            return response()->json(['message' => 'Não autorizado'], 401);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $period = $request->query('period');

        if ($period && !$startDate && !$endDate) {
            $dates = $this->resolvePeriod($period);
            $startDate = $dates['start'];
            $endDate = $dates['end'];
        }

        $profile = $this->deliveryService->getProfile($delivery, $startDate, $endDate);

        return response()->json($profile);
    }

    private function getDeliveryPerson(Request $request): ?DeliveryPerson
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        // Try Sanctum token first
        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken && $accessToken->tokenable instanceof DeliveryPerson) {
            return $accessToken->tokenable->status === 'active' ? $accessToken->tokenable : null;
        }

        // Fallback: old api_token column (transition period)
        $delivery = DeliveryPerson::where('api_token', $token)
            ->where('status', 'active')
            ->first();

        if ($delivery) {
            return $delivery;
        }

        return null;
    }

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay()->toDateTimeString(),
                'end' => now()->endOfDay()->toDateTimeString(),
            ],
            'week' => [
                'start' => now()->startOfWeek()->toDateTimeString(),
                'end' => now()->endOfDay()->toDateTimeString(),
            ],
            'month' => [
                'start' => now()->startOfMonth()->toDateTimeString(),
                'end' => now()->endOfDay()->toDateTimeString(),
            ],
            default => [
                'start' => null,
                'end' => null,
            ],
        };
    }
}
