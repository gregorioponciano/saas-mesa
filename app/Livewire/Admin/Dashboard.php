<?php

namespace App\Livewire\Admin;

use App\Models\CustomerPoint;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use App\Services\AuditService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\OrderLifecycleService;
use App\Services\PointsService;
use App\Services\RevenueService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Propriedades computadas ([Computed]) reconhecidas pelo PHPStan.
 *
 * @property object $revenueStats
 * @property object $overviewStats
 * @property mixed $pendingOrders
 * @property mixed $preparingOrders
 * @property mixed $activeOrders
 * @property mixed $tableGroups
 * @property mixed $occupiedTablesWithOrders
 * @property mixed $availableProducts
 * @property mixed $orderHistory
 * @property mixed $deliveryOrders
 * @property mixed $availableDeliveryPeople
 * @property mixed $lowStockProducts
 */
class Dashboard extends Component
{
    public string $period = 'today';

    public string $tab = 'overview';

    public array $revenueData = [];

    public bool $showOrderModal = false;

    public ?int $viewingOrderId = null;

    public ?array $viewingOrder = null;

    public bool $showAddItemModal = false;

    public ?int $addItemOrderId = null;

    public ?int $addItemProductId = null;

    public int $addItemQuantity = 1;

    public bool $addItemIsBonus = false;

    public string $addItemBonusReason = '';

    public bool $showPaymentModal = false;

    public ?int $paymentOrderId = null;

    public string $paymentMethod = 'pix';

    public float $paymentAmount = 0;

    public string $paymentNotes = '';

    public bool $showCloseTableModal = false;

    public ?int $closeTableId = null;

    public float $closeTableTotal = 0;

    public string $closeTablePaymentMethod = 'pix';

    public string $closeTablePaymentNotes = '';

    public bool $showPixQrModal = false;

    public ?string $pixQrCode = null;

    public ?string $pixCopiaECola = null;

    public bool $generatingPix = false;

    public float $pixAmount = 0;

    public string $historySearch = '';

    public string $historyPeriod = 'today';

    public string $historyTypeFilter = 'all';

    public bool $showStockModal = false;

    public ?int $stockAdjustmentProductId = null;

    public string $stockAdjustmentValue = '0';

    public bool $showCancelOrderModal = false;

    public ?int $cancelOrderId = null;

    public string $cancelReason = '';

    public float $cancelRefundAmount = 0;

    public bool $cancelHasPayment = false;

    protected $listeners = [
        'notifyNewOrder' => '$refresh',
        'orderUpdated' => '$refresh',
    ];

    public function mount(): void
    {
        $this->tab = request()->query('tab', 'overview');
        $this->loadRevenueChart();
    }

    public function switchTab(string $tab): void
    {
        if ($tab === 'grid' && ! auth()->user()->tenant_id) {
            $this->dispatch('notify', message: 'Nenhuma empresa vinculada à sua conta.', type: 'alert');

            return;
        }

        $this->tab = $tab;
    }

    public function updatedPeriod(): void
    {
        $this->loadRevenueChart();
    }

    public function loadRevenueChart(): void
    {
        $days = match ($this->period) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            default => 7,
        };

        $series = app(RevenueService::class)->revenueChart(auth()->user()->tenant_id, $days);

        $this->revenueData = [];
        foreach ($series as $date => $total) {
            $this->revenueData[] = [
                'date' => Carbon::parse($date)->format('d/m'),
                'total' => $total,
            ];
        }
    }

    #[Computed]
    public function revenueStats(): object
    {
        return app(RevenueService::class)->revenue(auth()->user()->tenant_id, $this->period);
    }

    #[Computed]
    public function pendingOrders()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'novo')->count();
    }

    #[Computed]
    public function preparingOrders()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'em_preparo')->count();
    }

    #[Computed]
    public function activeOrders()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->with('items', 'table', 'payments')
            ->latest()
            ->get();
    }

    #[Computed]
    public function myOrders()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('user_id', Auth::id())
            ->with('items', 'table')
            ->latest()
            ->take(20)
            ->get();
    }

    #[Computed]
    public function myActiveOrders()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('user_id', Auth::id())
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->with('items', 'table')
            ->latest()
            ->get();
    }

    #[Computed]
    public function orderHistory()
    {
        $query = Order::where('tenant_id', auth()->user()->tenant_id)->with('items', 'table');

        $query->whereIn('status', ['entregue', 'cancelado', 'fechado']);

        if ($this->historyPeriod === 'today') {
            $query->whereDate('created_at', now()->today());
        } elseif ($this->historyPeriod === 'week') {
            $query->whereDate('created_at', '>=', now()->subDays(7));
        } elseif ($this->historyPeriod === 'month') {
            $query->whereDate('created_at', '>=', now()->subDays(30));
        }

        if ($this->historySearch) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', "%{$this->historySearch}%")
                    ->orWhere('id', 'like', "%{$this->historySearch}%");
            });
        }

        $orders = $query->latest()->take(50)->get();

        $entries = collect();
        $processedIds = collect();

        $fechadoTableOrders = $orders->where('status', 'fechado')
            ->whereNotNull('table_id')
            ->whereNotNull('bill_closed_at');

        $groups = $fechadoTableOrders->groupBy(fn ($o) => $o->table_id.'-'.$o->bill_closed_at->timestamp);

        foreach ($groups as $group) {
            $first = $group->first();
            $ids = $group->pluck('id');
            $processedIds = $processedIds->merge($ids);

            if ($group->count() > 1) {
                $entries->push((object) [
                    'id' => $first->id,
                    'order_ids' => $ids->toArray(),
                    'display_id' => 'Mesa '.$first->table?->number,
                    'customer_name' => 'Mesa '.$first->table?->number,
                    'table_number' => $first->table?->number,
                    'total' => $group->sum('total'),
                    'typeLabel' => 'Mesa',
                    'typeClasses' => $first->typeClasses(),
                    'statusLabel' => 'Fechado',
                    'statusClasses' => $first->statusClasses(),
                    'created_at' => $first->bill_closed_at->format('d/m/Y H:i'),
                    'created_at_raw' => $first->bill_closed_at,
                    'cancelled_at' => null,
                    'is_cancelled' => false,
                    'paid_amount' => round((float) $group->sum(fn ($o) => $o->paidAmount()), 2),
                    'refunded_amount' => 0.0,
                    'payment_status' => $first->payment_status,
                    'cancelled_by_name' => null,
                    'cancellation_reason' => null,
                    'items' => $group->flatMap(fn ($o) => $o->items),
                    'address_json' => null,
                    'order_count' => $group->count(),
                    'is_grouped' => true,
                    'table_id' => $first->table_id,
                ]);
            } else {
                $entries->push($this->makeHistoryEntry($first));
            }
        }

        foreach ($orders as $order) {
            if (! $processedIds->contains($order->id)) {
                $entries->push($this->makeHistoryEntry($order));
            }
        }

        return $entries->sortByDesc(fn ($e) => $e->created_at_raw ?? $e->created_at)
            ->when($this->historyTypeFilter !== 'all', fn ($c) => $c->where('typeLabel', Order::TYPE_LABELS[$this->historyTypeFilter] ?? ucfirst($this->historyTypeFilter)))
            ->values();
    }

    private function makeHistoryEntry($order): object
    {
        return (object) [
            'id' => $order->id,
            'order_ids' => [$order->id],
            'display_id' => '#'.str_pad($order->id, 5, '0', STR_PAD_LEFT),
            'customer_name' => $order->customer_name,
            'table_number' => $order->table?->number,
            'total' => (float) $order->total,
            'typeLabel' => $order->typeLabel(),
            'typeClasses' => $order->typeClasses(),
            'statusLabel' => $order->statusLabel(),
            'statusClasses' => $order->statusClasses(),
            'created_at' => $order->bill_closed_at ? $order->bill_closed_at->format('d/m/Y H:i') : $order->created_at->format('d/m/Y H:i'),
            'created_at_raw' => $order->bill_closed_at ?? $order->created_at,
            'cancelled_at' => $order->cancelled_at?->format('d/m/Y H:i'),
            'is_cancelled' => $order->isCancelled(),
            'paid_amount' => round($order->paidAmount(), 2),
            'refunded_amount' => round($order->refundedAmount(), 2),
            'payment_status' => $order->payment_status,
            'cancelled_by_name' => $order->cancelledBy?->name,
            'cancellation_reason' => $order->cancellation_reason,
            'items' => $order->items,
            'address_json' => $order->address_json,
            'order_count' => 1,
            'is_grouped' => false,
            'table_id' => $order->table_id,
        ];
    }

    #[Computed]
    public function overviewStats(): object
    {
        $tenantId = auth()->user()->tenant_id;
        $tableQuery = Table::where('tenant_id', $tenantId);

        return (object) [
            'total_tables' => (clone $tableQuery)->count(),
            'free_tables' => (clone $tableQuery)->where('status', 'free')->count(),
            'occupied_tables' => (clone $tableQuery)->where('status', 'occupied')->count(),
            'reserved_tables' => (clone $tableQuery)->where('status', 'reserved')->count(),
            'total_delivery_cost' => (float) Order::where('tenant_id', $tenantId)
                ->whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
                ->where('type', 'entrega')
                ->sum('delivery_cost'),
            'pending_delivery_cost' => (float) Order::where('tenant_id', $tenantId)
                ->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
                ->where('type', 'entrega')
                ->sum('delivery_cost'),
            'pending_delivery_count' => Order::where('tenant_id', $tenantId)
                ->where('type', 'entrega')
                ->whereIn('status', ['novo', 'em_preparo'])
                ->count(),
        ];
    }

    #[Computed]
    public function occupiedTablesWithOrders()
    {
        return Table::where('tenant_id', auth()->user()->tenant_id)->where('status', 'occupied')
            ->withCount(['orders' => function ($q) {
                $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega']);
            }])
            ->orderByRaw('CAST(number AS UNSIGNED), number')
            ->get();
    }

    #[Computed]
    public function tableGroups()
    {
        return Table::where('tenant_id', auth()->user()->tenant_id)->where('status', 'occupied')
            ->with(['orders' => function ($q) {
                $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
                    ->with('items', 'payments')
                    ->latest();
            }])
            ->orderByRaw('CAST(number AS UNSIGNED), number')
            ->get()
            ->filter(fn ($table) => $table->orders->isNotEmpty())
            ->map(function ($table) {
                $total = (float) $table->orders->sum('total');
                $paid = (float) $table->orders->sum(fn ($o) => (float) $o->payments->where('status', 'paid')->sum('amount'));
                $pending = max(0, $total - $paid);
                $allDelivered = $table->orders->every(fn ($o) => in_array($o->status, ['entregue', 'saiu_entrega']));

                return (object) [
                    'table' => $table,
                    'orders' => $table->orders,
                    'total' => $total,
                    'paid' => $paid,
                    'pending' => $pending,
                    'canClose' => $pending <= 0 && $allDelivered,
                ];
            });
    }

    #[Computed]
    public function availableProducts()
    {
        $query = Product::where('tenant_id', auth()->user()->tenant_id)->active()->with('category');

        $tenant = auth()->user()?->tenant;
        if ($tenant && $tenant->hiddenProductsCount() > 0) {
            $query->whereIn('id', $tenant->manageableProductsIds());
        }

        return $query->orderBy('name')->get();
    }

    public function viewOrder(int $orderId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->with('items', 'table', 'user', 'payments')->findOrFail($orderId);
        $this->viewingOrderId = $orderId;
        $this->viewingOrder = [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'table_number' => $order->table?->number,
            'total' => (float) $order->total,
            'status' => $order->status,
            'type' => $order->type,
            'typeLabel' => $order->typeLabel(),
            'typeClasses' => $order->typeClasses(),
            'statusLabel' => $order->statusLabel(),
            'statusColor' => $order->statusClasses(),
            'created_at' => $order->created_at->format('d/m/Y H:i'),
            'payment_method' => $order->payment_method,
            'payment_change' => $order->payment_change ? (float) $order->payment_change : null,
            'notes' => $order->notes,
            'points_used' => (bool) ($order->points_used ?? false),
            'points_spent' => (int) ($order->points_spent ?? 0),
            'points_discount' => (float) ($order->points_discount ?? 0),
            'customer_points' => $order->user_id
                ? CustomerPoint::getBalance($order->tenant, $order->user)
                : 0,
            'address_json' => $order->address_json,
            'delivery_cost' => $order->delivery_cost ? (float) $order->delivery_cost : null,
            'delivery_person' => $order->deliveryPerson?->name ?? null,
            'is_fechado' => $order->isBillClosed(),
            'nextStatusLabel' => $order->nextStatus() ? ($order->statusFlowLabels()[$order->status] ?? 'Avançar') : null,
            'nextStatus' => $order->nextStatus(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->price * $item->quantity,
                'change_requested' => $item->change_requested,
                'change_note' => $item->change_note,
                'is_bonificacao' => $item->isBonificacao(),
                'bonificacao_reason' => $item->bonificacao_reason,
                'is_cancelled' => $item->isCancelled(),
            ])->toArray(),
            'payments' => $order->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'payment_method_label' => $p->paymentMethodLabel(),
                'status' => $p->status,
                'status_label' => $p->statusLabel(),
                'status_classes' => $p->statusClasses(),
                'paid_at' => $p->paid_at?->format('d/m/Y H:i'),
                'notes' => $p->notes,
            ])->toArray(),
            'pending_payment' => $order->pendingPaymentAmount(),
            'has_payment' => $order->hasPayment(),
            'paid_amount' => round($order->paidAmount(), 2),
            'refunded_amount' => round($order->refundedAmount(), 2),
            'payment_status' => $order->payment_status,
            'cancelled_by_name' => $order->cancelledBy?->name,
            'cancellation_reason' => $order->cancellation_reason,
        ];
        $this->showOrderModal = true;
    }

    public function closeOrderModal(): void
    {
        $this->showOrderModal = false;
        $this->viewingOrderId = null;
        $this->viewingOrder = null;
        $this->showAddItemModal = false;
        $this->addItemOrderId = null;
        $this->addItemProductId = null;
        $this->addItemQuantity = 1;
        $this->addItemIsBonus = false;
        $this->addItemBonusReason = '';
        $this->showPaymentModal = false;
        $this->paymentOrderId = null;
        $this->paymentMethod = 'pix';
        $this->paymentAmount = 0;
        $this->paymentNotes = '';
        $this->showCloseTableModal = false;
        $this->closeTableId = null;
        $this->closeTableTotal = 0;
        $this->closeTablePaymentMethod = 'pix';
        $this->closeTablePaymentNotes = '';
    }

    public function openAddItem(int $orderId): void
    {
        $this->addItemOrderId = $orderId;
        $this->addItemProductId = null;
        $this->addItemQuantity = 1;
        $this->addItemIsBonus = false;
        $this->addItemBonusReason = '';
        $this->showAddItemModal = true;
    }

    public function addItemToOrder(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->validate([
            'addItemProductId' => 'required|exists:products,id',
            'addItemQuantity' => 'required|integer|min:1|max:99',
        ]);

        $product = Product::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->addItemProductId);
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->addItemOrderId);

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada, nao e possivel adicionar itens.');

            return;
        }

        if ($product->stock < $this->addItemQuantity) {
            $this->dispatch('notify', message: "{$product->name} possui apenas {$product->stock} unidade(s) em estoque.");

            return;
        }

        $isBonus = $this->addItemIsBonus;
        $price = $isBonus ? 0.0 : (float) $product->price;
        $bonusReason = $isBonus ? trim($this->addItemBonusReason) : null;

        DB::transaction(function () use ($product, $order, $price, $isBonus, $bonusReason) {
            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $this->addItemQuantity,
                'price' => $price,
                'is_bonificacao' => $isBonus,
                'bonificacao_reason' => $bonusReason,
            ]);

            $order->increment('total', $price * $this->addItemQuantity);

            app(StockService::class)->deductStock(
                $product->id,
                $this->addItemQuantity,
                $order->tenant_id,
                $order->id,
                Auth::id(),
                $isBonus ? 'bonificacao' : 'sale',
                $isBonus ? "Bonificacao (cortesia) - Pedido #{$order->id}" : "Adicao de item - Pedido #{$order->id}",
                $item->id
            );
        });

        $this->showAddItemModal = false;
        $this->addItemProductId = null;
        $this->addItemQuantity = 1;
        $this->addItemIsBonus = false;
        $this->addItemBonusReason = '';
        $this->viewOrder($order->id);
        $this->dispatch('notify', message: $isBonus
            ? "{$product->name} adicionado ao pedido #{$order->id} como cortesia!"
            : "{$product->name} adicionado ao pedido #{$order->id}!");
        $this->dispatch('orderUpdated');
    }

    public function markItemAsBonus(int $itemId, string $bonusReason = ''): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $item = OrderItem::with('order')->findOrFail($itemId);
        $order = $item->order;

        if (! $order || $order->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada, nao e possivel bonificar itens.');

            return;
        }

        if ($item->isCancelled() || $item->is_points_item) {
            $this->dispatch('notify', message: 'Este item nao pode ser bonificado.', type: 'error');

            return;
        }

        if ($item->isBonificacao()) {
            $this->dispatch('notify', message: 'Item ja e cortesia.', type: 'error');

            return;
        }

        DB::transaction(function () use ($item, $order, $bonusReason) {
            $item->update([
                'is_bonificacao' => true,
                'bonificacao_reason' => $bonusReason ? substr($bonusReason, 0, 255) : null,
            ]);

            $order->decrement('total', (float) $item->price * (int) $item->quantity);
        });

        app(AuditService::class)->log(
            'order.item_bonificacao',
            "Item #{$item->id} do pedido #{$order->id} bonificado (cortesia) por ".auth()->user()->roleLabel().($bonusReason ? " - Motivo: {$bonusReason}" : ''),
            ['order_id' => $order->id, 'item_id' => $item->id, 'actor_id' => auth()->id(), 'reason' => $bonusReason],
            $order->tenant,
            'order',
            (string) $order->id
        );

        $this->viewOrder($order->id);
        $this->dispatch('notify', message: 'Item marcado como cortesia (bonificacao). O cliente nao paga por ele.');
        $this->dispatch('orderUpdated');
    }

    public function removeItemFromOrder(int $itemId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $item = OrderItem::with('order')->findOrFail($itemId);
        $order = $item->order;

        if (! $order || $order->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada.');

            return;
        }

        $result = app(OrderLifecycleService::class)->cancelItem(
            $item,
            auth()->user(),
            OrderLifecycleService::ACTOR_ADMIN
        );

        if (! $result['success']) {
            $this->dispatch('notify', message: $result['error'] ?? 'Nao foi possivel remover o item.', type: 'error');

            return;
        }

        $this->viewOrder($order->id);
        $this->dispatch('notify', message: 'Item removido do pedido!');
        $this->dispatch('orderUpdated');
    }

    public function openPaymentModal(int $orderId): void
    {
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $this->paymentOrderId = $orderId;
        $this->paymentAmount = $order->pendingPaymentAmount();
        $this->paymentMethod = 'pix';
        $this->paymentNotes = '';
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
        $this->showPaymentModal = true;
    }

    public function generatePaymentPix(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
        ]);

        $this->generatingPix = true;
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;

        try {
            $tenant = auth()->user()->tenant;
            $txid = 'pay'.$this->paymentOrderId.now()->format('YmdHis').rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData($tenant, $this->paymentAmount, $txid);
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixQrCode = $charge['qrcode'] ?? null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: '.$e->getMessage());
        }

        $this->generatingPix = false;
    }

    public function registerPayment(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|string',
        ]);

        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->paymentOrderId);

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada.');
            $this->showPaymentModal = false;

            return;
        }

        Payment::create([
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'amount' => $this->paymentAmount,
            'payment_method' => $this->paymentMethod,
            'status' => 'paid',
            'paid_at' => now(),
            'notes' => $this->paymentNotes,
        ]);

        try {
            app(PointsService::class)->grantPointsForOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Erro ao conceder pontos no pagamento manual', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
        }

        if ($order->pendingPaymentAmount() <= 0 && ! $order->table_id) {
            $order->update([
                'status' => 'fechado',
                'bill_closed_at' => now(),
            ]);
        }

        $this->showPaymentModal = false;
        $this->paymentOrderId = null;
        $this->paymentAmount = 0;
        $this->paymentNotes = '';
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;

        if ($this->showOrderModal) {
            $this->viewOrder($order->id);
        }

        $this->dispatch('notify', message: 'Pagamento registrado com sucesso!');
        $this->dispatch('orderUpdated');
    }

    public function closeBill(int $orderId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        if ($order->isBillClosed()) {
            return;
        }

        if ($order->table_id) {
            $this->dispatch('notify', message: 'Fechamento individual nao permitido. Use "Fechar Conta da Mesa".');

            return;
        }

        if (! $order->hasPayment() && $order->pendingPaymentAmount() > 0) {
            $this->dispatch('notify', message: 'Registre o pagamento antes de fechar a conta.');

            return;
        }

        $order->update([
            'status' => 'fechado',
            'bill_closed_at' => now(),
        ]);

        try {
            app(PointsService::class)->grantPointsForOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Erro ao conceder pontos ao fechar conta', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
        }

        $this->closeOrderModal();
        $this->dispatch('notify', message: "Conta do pedido #{$order->id} fechada!");
        $this->dispatch('orderUpdated');
    }

    public function finalizeOrder(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        if (! $this->viewingOrderId) {
            return;
        }

        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->viewingOrderId);
        $nextStatus = $order->nextStatus();

        if ($nextStatus) {
            $order->update(['status' => $nextStatus]);
        }

        $this->closeOrderModal();
        $this->dispatch('notify', message: "Pedido #{$order->id} atualizado com sucesso!");
        $this->dispatch('orderUpdated');
    }

    public function reopenAccount(int $orderId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        if ($order->status !== 'fechado') {
            $this->dispatch('notify', message: 'Apenas contas fechadas podem ser reabertas.');

            return;
        }

        $result = app(OrderLifecycleService::class)->reopenAccount($order, auth()->user());

        if (! $result['success']) {
            $this->dispatch('notify', message: $result['error'] ?? 'Nao foi possivel reabrir a conta.', type: 'error');

            return;
        }

        if ($this->showOrderModal) {
            $this->viewOrder($orderId);
        }
        $this->dispatch('notify', message: "Conta #{$order->id} reaberta! Pagamentos permanecem registrados como pagos.");
        $this->dispatch('orderUpdated');
    }

    public function openCancelOrderModal(int $orderId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $this->cancelOrderId = $order->id;
        $this->cancelReason = '';
        $this->cancelRefundAmount = round($order->paidAmount(), 2);
        $this->cancelHasPayment = $order->hasPayment() || $order->orderPayments()->whereIn('status', ['paid', 'pending', 'processing'])->exists();
        $this->showCancelOrderModal = true;
    }

    public function closeCancelOrderModal(): void
    {
        $this->showCancelOrderModal = false;
        $this->cancelOrderId = null;
        $this->cancelReason = '';
        $this->cancelRefundAmount = 0;
        $this->cancelHasPayment = false;
    }

    public function confirmCancelOrder(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        if (! $this->cancelOrderId) {
            return;
        }

        $order = Order::where('tenant_id', auth()->user()->tenant_id)->with('table')->findOrFail($this->cancelOrderId);

        $result = app(OrderLifecycleService::class)->cancelOrder(
            $order,
            auth()->user(),
            OrderLifecycleService::ACTOR_ADMIN,
            $this->cancelReason ?: null
        );

        $this->closeCancelOrderModal();

        if (! $result['success']) {
            $this->dispatch('notify', message: $result['error'] ?? 'Nao foi possivel cancelar.', type: 'error');

            return;
        }

        $message = "Conta #{$order->id} cancelada!";
        if ($result['refunded_amount'] > 0) {
            $message .= ' R$ '.number_format($result['refunded_amount'], 2, ',', '.').' sera ressarcido ao cliente.';
        }

        $this->dispatch('notify', message: $message);
        $this->closeOrderModal();
        $this->dispatch('orderUpdated');
    }

    public function openCloseTableModal(int $tableId): void
    {
        $table = Table::where('tenant_id', auth()->user()->tenant_id)->with(['orders' => function ($q) {
            $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
                ->where('status', '!=', 'fechado');
        }])->findOrFail($tableId);

        $total = 0;
        foreach ($table->orders as $order) {
            if (! $order->isBillClosed()) {
                $total += (float) $order->total;
            }
        }

        $this->closeTableId = $tableId;
        $this->closeTableTotal = $total;
        $this->closeTablePaymentMethod = 'pix';
        $this->closeTablePaymentNotes = '';
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
        $this->showCloseTableModal = true;
    }

    public function generateCloseTablePix(): void
    {
        if ($this->closeTableTotal <= 0) {
            $this->dispatch('notify', message: 'Nenhum valor pendente.');

            return;
        }

        $this->generatingPix = true;
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;

        try {
            $tenant = auth()->user()->tenant;
            $txid = 'mesa'.$this->closeTableId.now()->format('YmdHis').rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData($tenant, $this->closeTableTotal, $txid);
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixQrCode = $charge['qrcode'] ?? null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: '.$e->getMessage());
        }

        $this->generatingPix = false;
    }

    public function confirmCloseTableBill(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->validate([
            'closeTablePaymentMethod' => 'required|string',
        ]);

        $table = Table::where('tenant_id', auth()->user()->tenant_id)->with(['orders' => function ($q) {
            $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
                ->where('status', '!=', 'fechado');
        }])->findOrFail($this->closeTableId);

        $totalPending = 0;
        $closedCount = 0;
        foreach ($table->orders as $order) {
            if (! $order->isBillClosed()) {
                $totalPending += $order->pendingPaymentAmount();
                $order->update([
                    'status' => 'fechado',
                    'bill_closed_at' => now(),
                ]);
                try {
                    app(PointsService::class)->grantPointsForOrder($order->fresh());
                } catch (\Throwable $e) {
                    Log::error('Erro ao conceder pontos fechamento mesa', [
                        'order_id' => $order->id, 'error' => $e->getMessage(),
                    ]);
                }
                $closedCount++;
            }
        }

        if ($closedCount > 0) {
            if ($totalPending > 0) {
                Payment::create([
                    'order_id' => $table->orders->first()->id,
                    'tenant_id' => $table->orders->first()->tenant_id,
                    'amount' => $totalPending,
                    'payment_method' => $this->closeTablePaymentMethod,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'notes' => $this->closeTablePaymentNotes ? "Fechamento mesa {$table->number}: ".$this->closeTablePaymentNotes : "Fechamento mesa {$table->number}",
                ]);
            }

            $table->update(['status' => 'free']);
            $this->dispatch('notify', message: "Conta da Mesa {$table->number} fechada! R$ ".number_format($totalPending, 2, ',', '.')." em {$closedCount} pedido(s). Pagamento: ".(Payment::PAYMENT_METHODS[$this->closeTablePaymentMethod] ?? 'Outro'));
        } else {
            $this->dispatch('notify', message: "Nenhum pedido da Mesa {$table->number} pode ser fechado.");
        }

        $this->closeOrderModal();
        $this->dispatch('orderUpdated');
    }

    public function closePixQrModal(): void
    {
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
    }

    public function updateStatus(int $orderId, string $status): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);

        if ($status === 'cancelado') {
            $result = app(OrderLifecycleService::class)->cancelOrder(
                $order,
                auth()->user(),
                OrderLifecycleService::ACTOR_ADMIN
            );

            if (! $result['success']) {
                $this->dispatch('notify', message: $result['error'] ?? 'Nao foi possivel cancelar.', type: 'error');

                return;
            }

            if ($result['refunded_amount'] > 0) {
                $this->dispatch('notify', message: 'Pedido cancelado! R$ '.number_format($result['refunded_amount'], 2, ',', '.').' sera ressarcido ao cliente.');
            }

            $order = $order->fresh();

            if ($order->table_id) {
                $hasOther = Table::hasOtherActiveOrders($order->table_id, $order->id);
                if (! $hasOther) {
                    $order->table()->update(['status' => 'free']);
                    $this->dispatch('tableFreed')->to('public.menu');
                    $this->dispatch('tableFreed')->to('public.cart');
                }
            }

            if ($this->showOrderModal && $this->viewingOrderId === $orderId) {
                $this->viewOrder($orderId);
            }

            $this->dispatch('notify', message: 'Status do pedido atualizado!');
            $this->dispatch('orderUpdated');

            return;
        }

        $order->update(['status' => $status]);

        if ($status === 'fechado') {
            try {
                app(PointsService::class)->grantPointsForOrder($order->fresh());
            } catch (\Throwable $e) {
                Log::error('Erro ao conceder pontos na atualizacao de status', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        if ($order->table_id && $status === 'novo') {
            $order->table()->update(['status' => 'occupied']);
        }

        if ($order->table_id && in_array($status, ['cancelado', 'fechado'])) {
            $hasOther = Table::hasOtherActiveOrders($order->table_id, $order->id);
            if (! $hasOther) {
                $order->table()->update(['status' => 'free']);
                $this->dispatch('tableFreed')->to('public.menu');
                $this->dispatch('tableFreed')->to('public.cart');
            }
        }

        if ($this->showOrderModal && $this->viewingOrderId === $orderId) {
            $this->viewOrder($orderId);
        }

        $this->dispatch('notify', message: 'Status do pedido atualizado!');
        $this->dispatch('orderUpdated');
    }

    public string $deliveryFilter = 'all';

    #[Computed]
    public function deliveryOrders()
    {
        $query = Order::where('tenant_id', auth()->user()->tenant_id)->where('type', 'entrega')
            ->with('items', 'table', 'deliveryPerson', 'payments');

        if ($this->deliveryFilter === 'pending') {
            $query->whereIn('status', ['novo', 'em_preparo']);
        } elseif ($this->deliveryFilter === 'in_transit') {
            $query->where('status', 'saiu_entrega');
        } elseif ($this->deliveryFilter === 'delivered') {
            $query->where('status', 'entregue');
        } elseif ($this->deliveryFilter === 'finished') {
            $query->whereIn('status', ['fechado', 'cancelado']);
        }

        return $query->latest()->take(50)->get();
    }

    #[Computed]
    public function availableDeliveryPeople()
    {
        return DeliveryPerson::where('tenant_id', Auth::user()->tenant_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function assignDeliveryPerson(int $orderId, int $deliveryPersonId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $delivery = DeliveryPerson::where('tenant_id', auth()->user()->tenant_id)->findOrFail($deliveryPersonId);

        $order->update([
            'delivery_person_id' => $delivery->id,
        ]);

        if ($order->status === 'novo' || $order->status === 'em_preparo') {
            $order->update(['status' => 'saiu_entrega']);
        }

        $this->dispatch('notify', message: "Entregador {$delivery->name} designado para pedido #{$order->id}!");
        $this->dispatch('orderUpdated');
    }

    public function removeDeliveryPerson(int $orderId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $order->update(['delivery_person_id' => null]);

        $this->dispatch('notify', message: "Entregador removido do pedido #{$order->id}!");
        $this->dispatch('orderUpdated');
    }

    // ─── Stock Management ─────────────────────────────────────

    public function openStockModal(int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)->findOrFail($productId);
        $this->stockAdjustmentProductId = $product->id;
        $this->stockAdjustmentValue = (string) $product->stock;
        $this->showStockModal = true;
    }

    public function closeStockModal(): void
    {
        $this->showStockModal = false;
        $this->stockAdjustmentProductId = null;
        $this->stockAdjustmentValue = '0';
    }

    public function adjustStock(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->validate([
            'stockAdjustmentValue' => 'required|integer|min:0|max:999999',
        ]);

        try {
            app(StockService::class)->adjustStock(
                $this->stockAdjustmentProductId,
                (int) $this->stockAdjustmentValue,
                auth()->user()->tenant_id,
                auth()->user()->id,
                'Ajuste manual pelo dashboard',
            );
            $this->closeStockModal();
            $this->dispatch('notify', message: 'Estoque atualizado com sucesso!');
            $this->dispatch('stockUpdated');
        } catch (\Throwable $e) {
            Log::error('Erro ao ajustar estoque pelo dashboard', [
                'product_id' => $this->stockAdjustmentProductId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', message: 'Erro ao atualizar estoque.', type: 'error');
        }
    }

    #[Computed]
    public function lowStockProducts()
    {
        $tenantId = auth()->user()->tenant_id;

        if (! $tenantId) {
            return collect();
        }

        return app(StockService::class)->getLowStockProducts($tenantId, 10);
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'revenueStats' => $this->revenueStats,
            'overviewStats' => $this->overviewStats,
            'pendingOrders' => $this->pendingOrders,
            'preparingOrders' => $this->preparingOrders,
            'activeOrders' => $this->activeOrders,
            'tableGroups' => $this->tableGroups,
            'occupiedTablesWithOrders' => $this->occupiedTablesWithOrders,
            'availableProducts' => $this->availableProducts,
            'orderHistory' => $this->orderHistory,
            'deliveryOrders' => $this->deliveryOrders,
            'availableDeliveryPeople' => $this->availableDeliveryPeople,
            'lowStockProducts' => $this->lowStockProducts,
        ])->extends('layouts.admin');
    }
}
