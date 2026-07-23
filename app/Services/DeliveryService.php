<?php

namespace App\Services;

use App\Models\DeliveryPerson;
use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class DeliveryService
{
    public function __construct(
        private readonly DeliveryNotificationService $notificationService
    ) {}

    public function login(string $phone, string $password): ?DeliveryPerson
    {
        $delivery = DeliveryPerson::where('phone', $phone)
            ->where('status', 'active')
            ->first();

        if (!$delivery || !$delivery->hasPassword() || !Hash::check($password, $delivery->password)) {
            return null;
        }

        return $delivery;
    }

    public function createToken(DeliveryPerson $delivery, string $deviceName = 'mobile'): NewAccessToken
    {
        $delivery->tokens()->where('name', $deviceName)->delete();
        return $delivery->createToken($deviceName, ['delivery'], now()->addDays(7));
    }

    public function logout(DeliveryPerson $delivery): void
    {
        $delivery->currentAccessToken()->delete();
    }

    public function getAvailableOrders(DeliveryPerson $delivery)
    {
        return Order::where('tenant_id', $delivery->tenant_id)
            ->where('type', 'entrega')
            ->whereIn('status', ['novo', 'em_preparo'])
            ->whereNull('delivery_person_id')
            ->with('items')
            ->latest()
            ->get()
            ->map(fn($o) => $this->formatOrder($o));
    }

    public function getMyOrders(DeliveryPerson $delivery)
    {
        return Order::where('tenant_id', $delivery->tenant_id)
            ->where('type', 'entrega')
            ->where('delivery_person_id', $delivery->id)
            ->with('items')
            ->latest()
            ->get()
            ->map(fn($o) => $this->formatOrder($o, true));
    }

    public function acceptOrder(DeliveryPerson $delivery, int $orderId): ?Order
    {
        $order = Order::where('tenant_id', $delivery->tenant_id)
            ->where('id', $orderId)
            ->where('type', 'entrega')
            ->whereNull('delivery_person_id')
            ->whereIn('status', ['novo', 'em_preparo'])
            ->first();

        if (!$order) {
            return null;
        }

        $order->update([
            'delivery_person_id' => $delivery->id,
            'status' => 'coletado',
            'accepted_at' => now(),
        ]);

        $this->notificationService->orderAccepted($order, $delivery);

        return $order->fresh();
    }

    public function refuseOrder(DeliveryPerson $delivery, int $orderId): bool
    {
        $order = Order::where('tenant_id', $delivery->tenant_id)
            ->where('id', $orderId)
            ->where('type', 'entrega')
            ->whereNull('delivery_person_id')
            ->whereIn('status', ['novo', 'em_preparo'])
            ->first();

        if (!$order) {
            return false;
        }

        $order->update([
            'delivery_person_id' => null,
            'status' => 'novo',
        ]);

        return true;
    }

    public function markPickedUp(DeliveryPerson $delivery, int $orderId): ?Order
    {
        $order = Order::where('tenant_id', $delivery->tenant_id)
            ->where('id', $orderId)
            ->where('delivery_person_id', $delivery->id)
            ->where('status', 'coletado')
            ->first();

        if (!$order) {
            return null;
        }

        $order->update([
            'status' => 'saiu_entrega',
            'picked_up_at' => now(),
        ]);

        $this->notificationService->orderPickedUp($order, $delivery);

        return $order->fresh();
    }

    public function markDelivered(
        DeliveryPerson $delivery,
        int $orderId,
        ?string $photoPath = null,
        ?float $lat = null,
        ?float $lng = null
    ): ?Order {
        $order = Order::where('tenant_id', $delivery->tenant_id)
            ->where('id', $orderId)
            ->where('delivery_person_id', $delivery->id)
            ->first();

        if (!$order || !in_array($order->status, ['saiu_entrega', 'coletado'])) {
            return null;
        }

        $data = [
            'status' => 'entregue',
            'delivered_at' => now(),
        ];

        if ($photoPath) {
            $data['delivery_photo_path'] = $photoPath;
        }
        if ($lat !== null) {
            $data['delivery_lat'] = $lat;
        }
        if ($lng !== null) {
            $data['delivery_lng'] = $lng;
        }

        $order->update($data);

        $this->notificationService->orderDelivered($order, $delivery);

        return $order->fresh();
    }

    public function cancelOrder(DeliveryPerson $delivery, int $orderId): ?Order
    {
        $order = Order::where('tenant_id', $delivery->tenant_id)
            ->where('id', $orderId)
            ->where('delivery_person_id', $delivery->id)
            ->whereNotIn('status', ['entregue', 'fechado', 'cancelado'])
            ->first();

        if (!$order) {
            return null;
        }

        $previousStatus = $order->status;
        $order->update(['status' => 'cancelado']);

        $this->handleCancelSideEffects($order, $previousStatus);

        return $order->fresh();
    }

    public function getProfile(DeliveryPerson $delivery, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['entregue', 'fechado']);

        if ($startDate) {
            $query->where('delivered_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('delivered_at', '<=', $endDate);
        }

        $deliveredOrders = $query->get();

        $totalDeliveries = $deliveredOrders->count();
        $earnings = $deliveredOrders->sum('delivery_cost');

        $totalTimeMinutes = $deliveredOrders
            ->filter(fn($o) => $o->accepted_at && $o->delivered_at)
            ->sum(fn($o) => $o->accepted_at->diffInMinutes($o->delivered_at));

        $avgTimeMinutes = $totalDeliveries > 0 ? round($totalTimeMinutes / $totalDeliveries, 1) : 0;

        $cancelledCount = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->where('status', 'cancelado')
            ->count();

        $cancelRate = ($totalDeliveries + $cancelledCount) > 0
            ? round(($cancelledCount / ($totalDeliveries + $cancelledCount)) * 100, 1)
            : 0;

        return [
            'id' => $delivery->id,
            'name' => $delivery->name,
            'phone' => $delivery->phone,
            'status' => $delivery->status,
            'earnings' => (float) $earnings,
            'total_deliveries' => $totalDeliveries,
            'avg_time_minutes' => $avgTimeMinutes,
            'cancel_rate' => $cancelRate,
            'has_password' => $delivery->hasPassword(),
            'activated_at' => $delivery->activated_at?->toIso8601String(),
        ];
    }

    public function getTodayStats(DeliveryPerson $delivery): array
    {
        $today = now()->startOfDay();

        $completedToday = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['entregue', 'fechado'])
            ->where('delivered_at', '>=', $today)
            ->get();

        $activeNow = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['coletado', 'saiu_entrega'])
            ->count();

        $pendingPickup = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->where('status', 'coletado')
            ->count();

        $todaysEarnings = (float) $completedToday->sum('delivery_cost');

        $completedFiltered = $completedToday->filter(fn($o) => $o->accepted_at && $o->delivered_at);
        $avgTimeToday = $completedFiltered->isNotEmpty()
            ? round($completedFiltered->avg(fn($o) => $o->accepted_at->diffInMinutes($o->delivered_at)), 1)
            : 0;

        return [
            'completed' => $completedToday->count(),
            'active' => $activeNow,
            'pending_pickup' => $pendingPickup,
            'earnings' => $todaysEarnings,
            'avg_time_today' => $avgTimeToday,
        ];
    }

    public function getWeeklyEarnings(DeliveryPerson $delivery): array
    {
        $startOfWeek = now()->startOfWeek();

        $daily = Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['entregue', 'fechado'])
            ->where('delivered_at', '>=', $startOfWeek)
            ->get()
            ->groupBy(fn($o) => $o->delivered_at->format('Y-m-d'));

        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $dayOrders = $daily->get($key, collect());
            $weekDays[] = [
                'date' => $date->format('D'),
                'orders' => $dayOrders->count(),
                'earnings' => (float) $dayOrders->sum('delivery_cost'),
            ];
        }

        return [
            'total_earnings' => (float) $daily->flatten()->sum('delivery_cost'),
            'total_orders' => $daily->flatten()->count(),
            'days' => $weekDays,
        ];
    }

    public function getDeliveryRanking(DeliveryPerson $delivery): array
    {
        $allDeliveries = DeliveryPerson::where('tenant_id', $delivery->tenant_id)
            ->active()
            ->get()
            ->map(function ($dp) {
                $completed = Order::where('tenant_id', $dp->tenant_id)
                    ->where('delivery_person_id', $dp->id)
                    ->whereIn('status', ['entregue', 'fechado'])
                    ->count();
                $avgOrders = Order::where('tenant_id', $dp->tenant_id)
                    ->where('delivery_person_id', $dp->id)
                    ->whereIn('status', ['entregue', 'fechado'])
                    ->whereNotNull('accepted_at')
                    ->whereNotNull('delivered_at')
                    ->get();
                $avgTime = $avgOrders->isNotEmpty()
                    ? round($avgOrders->avg(fn($o) => $o->accepted_at->diffInMinutes($o->delivered_at)), 1)
                    : 0;

                return ['id' => $dp->id, 'name' => $dp->name, 'completed' => $completed, 'avg_time' => $avgTime];
            })
            ->sortByDesc('completed')
            ->values()
            ->toArray();

        $myIndex = array_search($delivery->id, array_column($allDeliveries, 'id'));

        return [
            'ranking' => $allDeliveries,
            'my_position' => $myIndex !== false ? $myIndex + 1 : null,
            'total_deliverers' => count($allDeliveries),
        ];
    }

    public function getOrderHistory(DeliveryPerson $delivery, int $perPage = 10): LengthAwarePaginator
    {
        return Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['entregue', 'fechado', 'cancelado'])
            ->with('items')
            ->latest('delivered_at')
            ->paginate($perPage)
            ->through(fn($o) => $this->formatOrder($o, true));
    }

    public function getDeliveryReport(int $tenantId, ?string $startDate = null, ?string $endDate = null): array
    {
        $deliveryPeople = DeliveryPerson::where('tenant_id', $tenantId)->get();

        $report = [];

        foreach ($deliveryPeople as $dp) {
            $query = Order::where('tenant_id', $tenantId)
                ->where('delivery_person_id', $dp->id)
                ->whereIn('status', ['entregue', 'fechado']);

            if ($startDate) {
                $query->where('delivered_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('delivered_at', '<=', $endDate);
            }

            $delivered = $query->get();
            $total = $delivered->count();
            $earnings = $delivered->sum('delivery_cost');

            $totalMinutes = $delivered
                ->filter(fn($o) => $o->accepted_at && $o->delivered_at)
                ->sum(fn($o) => $o->accepted_at->diffInMinutes($o->delivered_at));

            $avgTime = $total > 0 ? round($totalMinutes / $total, 1) : 0;

            $cancelled = Order::where('tenant_id', $tenantId)
                ->where('delivery_person_id', $dp->id)
                ->where('status', 'cancelado')
                ->count();

            $cancelRate = ($total + $cancelled) > 0
                ? round(($cancelled / ($total + $cancelled)) * 100, 1)
                : 0;

            $report[] = [
                'id' => $dp->id,
                'name' => $dp->name,
                'phone' => $dp->phone,
                'status' => $dp->status,
                'total_deliveries' => $total,
                'earnings' => (float) $earnings,
                'avg_time_minutes' => $avgTime,
                'cancel_rate' => $cancelRate,
                'is_activated' => $dp->isActivated(),
            ];
        }

        return $report;
    }

    public function uploadDeliveryPhoto($photo, int $tenantId, int $deliveryPersonId): string
    {
        return $photo->store("delivery-photos/{$tenantId}/{$deliveryPersonId}", 'public');
    }

    public function uploadAvatar($avatar, int $tenantId): string
    {
        return $avatar->store("delivery-avatars/{$tenantId}", 'public');
    }

    public function toggleAvailability(DeliveryPerson $delivery): bool
    {
        $newStatus = $delivery->status === 'active' ? 'inactive' : 'active';
        $delivery->update(['status' => $newStatus]);
        return $newStatus === 'active';
    }

    private function formatOrder(Order $order, bool $includeDeliveryCost = false): array
    {
        $data = [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'address' => $order->address_json['address'] ?? '',
            'zipcode' => $order->address_json['zipcode'] ?? '',
            'reference' => $order->address_json['reference'] ?? '',
            'total' => (float) $order->total,
            'payment_method' => match ($order->payment_method) {
                'cash' => 'Dinheiro',
                'credit' => 'Cartão de Crédito',
                'debit' => 'Cartão de Débito',
                'pix' => 'Pix',
                'card' => 'Cartão',
                default => $order->payment_method ?? '-',
            },
            'payment_change' => (float) ($order->payment_change ?? 0),
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'status_dot' => $order->statusDotColor(),
            'status_animated' => $order->statusAnimated(),
            'items' => $order->items->map(fn($i) => [
                'product' => $i->product_name,
                'quantity' => $i->quantity,
                'price' => (float) $i->price,
                'options' => $i->selected_options_json,
            ]),
            'items_count' => $order->items->sum('quantity'),
            'notes' => $order->notes,
            'created_at' => $order->created_at->format('d/m/Y H:i'),
            'created_at_diff' => $order->created_at->diffForHumans(),
            'accepted_at' => $order->accepted_at?->format('d/m/Y H:i'),
            'picked_up_at' => $order->picked_up_at?->format('d/m/Y H:i'),
            'delivered_at' => $order->delivered_at?->format('d/m/Y H:i'),
        ];

        if ($includeDeliveryCost) {
            $data['delivery_cost'] = (float) ($order->delivery_cost ?? 0);
        }

        return $data;
    }

    private function handleCancelSideEffects(Order $order, string $previousStatus): void
    {
        try {
            app(PointsService::class)->reversePointsForOrder($order);
            app(PointsService::class)->refundSpentPointsForOrder($order);
        } catch (\Throwable $e) {
            Log::error('Erro ao processar pontos cancelamento delivery', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
        }

        if (!in_array($previousStatus, ['entregue', 'fechado'])) {
            try {
                app(StockService::class)->returnOrderStock($order->fresh(), $order->delivery_person_id);
            } catch (\Throwable $e) {
                Log::error('Erro ao devolver estoque cancelamento delivery', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
