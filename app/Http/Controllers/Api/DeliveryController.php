<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPerson;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeliveryController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'required|string',
        ]);

        $delivery = DeliveryPerson::where('phone', $request->phone)
            ->where('name', $request->name)
            ->where('status', 'active')
            ->first();

        if (!$delivery) {
            return response()->json(['message' => 'Credenciais invalidas'], 401);
        }

        if (!$delivery->api_token) {
            $delivery->update(['api_token' => Str::random(60)]);
            $delivery->refresh();
        }

        return response()->json([
            'token' => $delivery->api_token,
            'name' => $delivery->name,
            'phone' => $delivery->phone,
            'id' => $delivery->id,
        ]);
    }

    public function orders(Request $request)
    {
        $delivery = $this->getDeliveryPerson($request);

        if (!$delivery) {
            return response()->json(['message' => 'Nao autorizado'], 401);
        }

        $orders = Order::where('tenant_id', $delivery->tenant_id)
            ->where('type', 'entrega')
            ->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->whereNull('delivery_person_id')
            ->with('items', 'table')
            ->latest()
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'customer_name' => $o->customer_name,
                'customer_phone' => $o->customer_phone,
                'address' => $o->address_json['address'] ?? '',
                'reference' => $o->address_json['reference'] ?? '',
                'total' => (float) $o->total,
                'status' => $o->status,
                'items' => $o->items->map(fn($i) => [
                    'product' => $i->product_name,
                    'quantity' => $i->quantity,
                    'price' => (float) $i->price,
                ]),
                'notes' => $o->notes,
                'created_at' => $o->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function myOrders(Request $request)
    {
        $delivery = $this->getDeliveryPerson($request);

        if (!$delivery) {
            return response()->json(['message' => 'Nao autorizado'], 401);
        }

        $orders = Order::where('tenant_id', $delivery->tenant_id)
            ->where('type', 'entrega')
            ->where('delivery_person_id', $delivery->id)
            ->with('items', 'table')
            ->latest()
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'customer_name' => $o->customer_name,
                'customer_phone' => $o->customer_phone,
                'address' => $o->address_json['address'] ?? '',
                'reference' => $o->address_json['reference'] ?? '',
                'total' => (float) $o->total,
                'delivery_cost' => (float) ($o->delivery_cost ?? 0),
                'status' => $o->status,
                'status_label' => $o->statusLabel(),
                'items' => $o->items->map(fn($i) => [
                    'product' => $i->product_name,
                    'quantity' => $i->quantity,
                    'price' => (float) $i->price,
                ]),
                'notes' => $o->notes,
                'created_at' => $o->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function acceptOrder(Request $request, int $orderId)
    {
        $delivery = $this->getDeliveryPerson($request);

        if (!$delivery) {
            return response()->json(['message' => 'Nao autorizado'], 401);
        }

        $order = Order::where('tenant_id', $delivery->tenant_id)
            ->where('id', $orderId)
            ->where('type', 'entrega')
            ->whereNull('delivery_person_id')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Pedido nao encontrado ou ja foi aceito'], 404);
        }

        $order->update([
            'delivery_person_id' => $delivery->id,
            'status' => 'saiu_entrega',
        ]);

        return response()->json(['message' => 'Pedido aceito com sucesso', 'order_id' => $order->id]);
    }

    public function updateStatus(Request $request, int $orderId)
    {
        $request->validate([
            'status' => 'required|in:entregue,cancelado',
        ]);

        $delivery = $this->getDeliveryPerson($request);

        if (!$delivery) {
            return response()->json(['message' => 'Nao autorizado'], 401);
        }

        $order = Order::where('tenant_id', $delivery->tenant_id)
            ->where('id', $orderId)
            ->where('delivery_person_id', $delivery->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Pedido nao encontrado'], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json(['message' => 'Status atualizado', 'status' => $order->status]);
    }

    public function profile(Request $request)
    {
        $delivery = $this->getDeliveryPerson($request);

        if (!$delivery) {
            return response()->json(['message' => 'Nao autorizado'], 401);
        }

        $earnings = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['entregue', 'fechado'])
            ->sum('delivery_cost');

        $totalDeliveries = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['entregue', 'fechado'])
            ->count();

        return response()->json([
            'id' => $delivery->id,
            'name' => $delivery->name,
            'phone' => $delivery->phone,
            'status' => $delivery->status,
            'earnings' => (float) $earnings,
            'total_deliveries' => $totalDeliveries,
        ]);
    }

    private function getDeliveryPerson(Request $request): ?DeliveryPerson
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        return DeliveryPerson::where('api_token', $token)
            ->where('status', 'active')
            ->first();
    }
}
