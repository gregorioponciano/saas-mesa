<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderTrackingController extends Controller
{
    public function status(int $id): JsonResponse
    {
        $order = Order::withoutTenant()->with(['deliveryPerson', 'items'])->find($id);

        if (! $order || ! $order->isEntrega()) {
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        }

        $deliveryPerson = $order->deliveryPerson;

        $items = $order->items->map(function ($item) {
            return [
                'product_name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
            ];
        })->values();

        $statusTimeline = $this->buildStatusTimeline($order);

        return response()->json([
            'status' => $order->status,
            'status_label' => Order::STATUS_LABELS[$order->status] ?? $order->status,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'total' => (float) $order->total,
            'delivery_person' => $deliveryPerson ? [
                'name' => $deliveryPerson->name,
                'phone' => $deliveryPerson->phone,
                'vehicle_plate' => $deliveryPerson->vehicle_plate,
                'vehicle_model' => $deliveryPerson->vehicle_model,
            ] : null,
            'items' => $items,
            'address' => $order->address_json,
            'created_at_diff' => $order->created_at->diffForHumans(),
            'created_at' => $order->created_at->toIso8601String(),
            'status_timeline' => $statusTimeline,
        ]);
    }

    private function buildStatusTimeline(Order $order): array
    {
        $steps = match ($order->type) {
            'entrega' => ['novo', 'em_preparo', 'coletado', 'saiu_entrega', 'entregue'],
            default => ['novo', 'em_preparo', 'pronto', 'entregue'],
        };

        $currentIndex = array_search($order->status, $steps);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        $timestamps = [
            'novo' => $order->created_at,
            'coletado' => $order->accepted_at,
            'saiu_entrega' => $order->picked_up_at,
            'entregue' => $order->delivered_at,
        ];

        return array_map(function (string $status, int $index) use ($currentIndex, $timestamps) {
            return [
                'status' => $status,
                'label' => Order::STATUS_LABELS[$status] ?? $status,
                'reached' => $index <= $currentIndex,
                'current' => $index === $currentIndex,
                'timestamp' => $timestamps[$status] ? $timestamps[$status]->toIso8601String() : null,
            ];
        }, $steps, array_keys($steps));
    }
}
