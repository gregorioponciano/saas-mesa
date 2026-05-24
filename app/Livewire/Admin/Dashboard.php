<?php

namespace App\Livewire\Admin;

use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

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

    public string $historySearch = '';
    public string $historyPeriod = 'today';

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

        $startDate = Carbon::now()->subDays($days - 1);

        $orders = Order::where('created_at', '>=', $startDate)
            ->whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $this->revenueData = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $this->revenueData[] = [
                'date' => Carbon::parse($date)->format('d/m'),
                'total' => (float) ($orders[$date]->total ?? 0),
            ];
        }
    }

    #[Computed]
    public function totalRevenue()
    {
        return Order::whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])->sum('total');
    }

    #[Computed]
    public function deliveryRevenue()
    {
        return Order::whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->where('type', 'entrega')
            ->sum('total');
    }

    #[Computed]
    public function tableRevenue()
    {
        return Order::whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->where('type', 'mesa')
            ->sum('total');
    }

    #[Computed]
    public function pickupRevenue()
    {
        return Order::whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->where('type', 'retirada')
            ->sum('total');
    }

    #[Computed]
    public function ordersToday()
    {
        return Order::whereDate('created_at', Carbon::today())->count();
    }

    #[Computed]
    public function deliveryOrdersToday()
    {
        return Order::whereDate('created_at', Carbon::today())
            ->where('type', 'entrega')
            ->count();
    }

    #[Computed]
    public function tableOrdersToday()
    {
        return Order::whereDate('created_at', Carbon::today())
            ->where('type', 'mesa')
            ->count();
    }

    #[Computed]
    public function pickupOrdersToday()
    {
        return Order::whereDate('created_at', Carbon::today())
            ->where('type', 'retirada')
            ->count();
    }

    #[Computed]
    public function pendingOrders()
    {
        return Order::where('status', 'novo')->count();
    }

    #[Computed]
    public function preparingOrders()
    {
        return Order::where('status', 'em_preparo')->count();
    }

    #[Computed]
    public function activeOrders()
    {
        return Order::whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->with('items', 'table', 'payments')
            ->latest()
            ->get();
    }

    #[Computed]
    public function myOrders()
    {
        return Order::where('user_id', Auth::id())
            ->with('items', 'table')
            ->latest()
            ->take(20)
            ->get();
    }

    #[Computed]
    public function myActiveOrders()
    {
        return Order::where('user_id', Auth::id())
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->with('items', 'table')
            ->latest()
            ->get();
    }

    #[Computed]
    public function orderHistory()
    {
        $query = Order::with('items', 'table');

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

        return $query->latest()->take(50)->get();
    }

    #[Computed]
    public function tableStats()
    {
        $total = Table::count();
        $free = Table::where('status', 'free')->count();
        $occupied = Table::where('status', 'occupied')->count();
        $reserved = Table::where('status', 'reserved')->count();

        return compact('total', 'free', 'occupied', 'reserved');
    }

    #[Computed]
    public function occupiedTablesWithOrders()
    {
        return Table::where('status', 'occupied')
            ->withCount(['orders' => function ($q) {
                $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega']);
            }])
            ->orderByRaw("CAST(number AS UNSIGNED), number")
            ->get();
    }

    #[Computed]
    public function tableGroups()
    {
        return Table::where('status', 'occupied')
            ->with(['orders' => function ($q) {
                $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
                  ->with('items', 'payments')
                  ->latest();
            }])
            ->orderByRaw("CAST(number AS UNSIGNED), number")
            ->get()
            ->filter(fn($table) => $table->orders->isNotEmpty())
            ->map(function ($table) {
                $total = (float) $table->orders->sum('total');
                $paid = (float) $table->orders->sum(fn($o) => (float) $o->payments->where('status', 'paid')->sum('amount'));
                $pending = max(0, $total - $paid);
                $allDelivered = $table->orders->every(fn($o) => in_array($o->status, ['entregue', 'saiu_entrega']));
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
        return Product::active()->with('category')->orderBy('name')->get();
    }

    public function viewOrder(int $orderId): void
    {
        $order = Order::with('items', 'table', 'user', 'payments')->findOrFail($orderId);
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
            'address_json' => $order->address_json,
            'delivery_cost' => $order->delivery_cost ? (float) $order->delivery_cost : null,
            'delivery_person' => $order->deliveryPerson?->name ?? null,
            'is_fechado' => $order->isBillClosed(),
            'nextStatusLabel' => $order->nextStatus() ? ($order->statusFlowLabels()[$order->status] ?? 'Avançar') : null,
            'nextStatus' => $order->nextStatus(),
            'items' => $order->items->map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->price * $item->quantity,
                'change_requested' => $item->change_requested,
                'change_note' => $item->change_note,
            ])->toArray(),
            'payments' => $order->payments->map(fn($p) => [
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
        $this->showAddItemModal = true;
    }

    public function addItemToOrder(): void
    {
        $this->validate([
            'addItemProductId' => 'required|exists:products,id',
            'addItemQuantity' => 'required|integer|min:1|max:99',
        ]);

        $product = Product::findOrFail($this->addItemProductId);
        $order = Order::findOrFail($this->addItemOrderId);

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada, nao e possivel adicionar itens.');
            return;
        }

        $price = (float) $product->price;

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $this->addItemQuantity,
            'price' => $price,
        ]);

        $order->increment('total', $price * $this->addItemQuantity);

        $this->showAddItemModal = false;
        $this->addItemProductId = null;
        $this->addItemQuantity = 1;
        $this->viewOrder($order->id);
        $this->dispatch('notify', message: "{$product->name} adicionado ao pedido #{$order->id}!");
        $this->dispatch('orderUpdated');
    }

    public function removeItemFromOrder(int $itemId): void
    {
        $item = OrderItem::with('order')->findOrFail($itemId);
        $order = $item->order;

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada.');
            return;
        }

        $subtotal = (float) $item->price * $item->quantity;
        $item->delete();
        $order->decrement('total', $subtotal);

        $this->viewOrder($order->id);
        $this->dispatch('notify', message: 'Item removido do pedido!');
        $this->dispatch('orderUpdated');
    }

    public function openPaymentModal(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $this->paymentOrderId = $orderId;
        $this->paymentAmount = $order->pendingPaymentAmount();
        $this->paymentMethod = 'pix';
        $this->paymentNotes = '';
        $this->showPaymentModal = true;
    }

    public function registerPayment(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|string',
        ]);

        $order = Order::findOrFail($this->paymentOrderId);

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

        if ($order->pendingPaymentAmount() <= 0 && !$order->table_id) {
            $order->update([
                'status' => 'fechado',
                'bill_closed_at' => now(),
            ]);
        }

        $this->showPaymentModal = false;
        $this->paymentOrderId = null;
        $this->paymentAmount = 0;
        $this->paymentNotes = '';

        if ($this->showOrderModal) {
            $this->viewOrder($order->id);
        }

        $this->dispatch('notify', message: 'Pagamento registrado com sucesso!');
        $this->dispatch('orderUpdated');
    }

    public function closeBill(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        if ($order->isBillClosed()) {
            return;
        }

        if ($order->table_id) {
            $this->dispatch('notify', message: 'Fechamento individual nao permitido. Use "Fechar Conta da Mesa".');
            return;
        }

        if (!$order->hasPayment() && $order->pendingPaymentAmount() > 0) {
            $this->dispatch('notify', message: 'Registre o pagamento antes de fechar a conta.');
            return;
        }

        $order->update([
            'status' => 'fechado',
            'bill_closed_at' => now(),
        ]);

        $this->closeOrderModal();
        $this->dispatch('notify', message: "Conta do pedido #{$order->id} fechada!");
        $this->dispatch('orderUpdated');
    }

    public function finalizeOrder(): void
    {
        if (!$this->viewingOrderId) {
            return;
        }

        $order = Order::findOrFail($this->viewingOrderId);
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
        $order = Order::findOrFail($orderId);
        if ($order->status !== 'fechado') {
            $this->dispatch('notify', message: 'Apenas contas fechadas podem ser reabertas.');
            return;
        }
        $order->update(['status' => 'entregue', 'bill_closed_at' => null]);

        if ($this->showOrderModal) {
            $this->viewOrder($orderId);
        }
        $this->dispatch('notify', message: "Conta #{$order->id} reaberta!");
        $this->dispatch('orderUpdated');
    }

    public function cancelClosedOrder(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        if ($order->status !== 'fechado') {
            $this->dispatch('notify', message: 'Apenas contas fechadas podem ser canceladas.');
            return;
        }

        $order->update(['status' => 'cancelado', 'bill_closed_at' => null]);

        if ($order->table_id) {
            $hasOther = \App\Models\Table::hasOtherActiveOrders($order->table_id, $order->id);
            if (!$hasOther) {
                $order->table()->update(['status' => 'free']);
            }
        }

        $this->dispatch('notify', message: "Conta #{$order->id} cancelada!");
        $this->dispatch('orderUpdated');
    }

    public function openCloseTableModal(int $tableId): void
    {
        $table = Table::with(['orders' => function ($q) {
            $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
              ->where('status', '!=', 'fechado');
        }])->findOrFail($tableId);

        $total = 0;
        foreach ($table->orders as $order) {
            if (!$order->isBillClosed()) {
                $total += (float) $order->total;
            }
        }

        $this->closeTableId = $tableId;
        $this->closeTableTotal = $total;
        $this->closeTablePaymentMethod = 'pix';
        $this->closeTablePaymentNotes = '';
        $this->showCloseTableModal = true;
    }

    public function confirmCloseTableBill(): void
    {
        $this->validate([
            'closeTablePaymentMethod' => 'required|string',
        ]);

        $table = Table::with(['orders' => function ($q) {
            $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
              ->where('status', '!=', 'fechado');
        }])->findOrFail($this->closeTableId);

        $totalPending = 0;
        $closedCount = 0;
        foreach ($table->orders as $order) {
            if (!$order->isBillClosed()) {
                $totalPending += $order->pendingPaymentAmount();
                $order->update([
                    'status' => 'fechado',
                    'bill_closed_at' => now(),
                ]);
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
                    'notes' => $this->closeTablePaymentNotes ? "Fechamento mesa {$table->number}: " . $this->closeTablePaymentNotes : "Fechamento mesa {$table->number}",
                ]);
            }

            $table->update(['status' => 'free']);
            $this->dispatch('notify', message: "Conta da Mesa {$table->number} fechada! R$ " . number_format($totalPending, 2, ',', '.') . " em {$closedCount} pedido(s). Pagamento: " . ($this->closeTablePaymentMethod === 'pix' ? 'PIX' : ($this->closeTablePaymentMethod === 'credit_card' ? 'Cartao Credito' : ($this->closeTablePaymentMethod === 'debit_card' ? 'Cartao Debito' : 'Dinheiro'))));
        } else {
            $this->dispatch('notify', message: "Nenhum pedido da Mesa {$table->number} pode ser fechado.");
        }

        $this->closeOrderModal();
        $this->dispatch('orderUpdated');
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);

        if ($this->showOrderModal && $this->viewingOrderId === $orderId) {
            $this->viewOrder($orderId);
        }

        $this->dispatch('notify', message: 'Status do pedido atualizado!');
        $this->dispatch('orderUpdated');
    }

    #[Computed]
    public function pendingDeliveryCount()
    {
        return Order::where('type', 'entrega')
            ->whereIn('status', ['novo', 'em_preparo'])
            ->count();
    }

    #[Computed]
    public function occupiedTablesCount()
    {
        return Table::where('status', 'occupied')->count();
    }

    #[Computed]
    public function totalDeliveryCost()
    {
        return Order::whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->where('type', 'entrega')
            ->sum('delivery_cost');
    }

    #[Computed]
    public function pendingDeliveryCost()
    {
        return Order::whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->where('type', 'entrega')
            ->sum('delivery_cost');
    }

    public string $deliveryFilter = 'all';

    #[Computed]
    public function deliveryOrders()
    {
        $query = Order::where('type', 'entrega')
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
        $order = Order::findOrFail($orderId);
        $delivery = DeliveryPerson::findOrFail($deliveryPersonId);

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
        $order = Order::findOrFail($orderId);
        $order->update(['delivery_person_id' => null]);

        $this->dispatch('notify', message: "Entregador removido do pedido #{$order->id}!");
        $this->dispatch('orderUpdated');
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'totalRevenue' => $this->totalRevenue,
            'deliveryRevenue' => $this->deliveryRevenue,
            'tableRevenue' => $this->tableRevenue,
            'pickupRevenue' => $this->pickupRevenue,
            'ordersToday' => $this->ordersToday,
            'deliveryOrdersToday' => $this->deliveryOrdersToday,
            'tableOrdersToday' => $this->tableOrdersToday,
            'pickupOrdersToday' => $this->pickupOrdersToday,
            'pendingOrders' => $this->pendingOrders,
            'preparingOrders' => $this->preparingOrders,
            'pendingDeliveryCount' => $this->pendingDeliveryCount,
            'occupiedTablesCount' => $this->occupiedTablesCount,
            'activeOrders' => $this->activeOrders,
            'tableGroups' => $this->tableGroups,
            'tableStats' => $this->tableStats,
            'occupiedTablesWithOrders' => $this->occupiedTablesWithOrders,
            'availableProducts' => $this->availableProducts,
            'orderHistory' => $this->orderHistory,
            'totalDeliveryCost' => $this->totalDeliveryCost,
            'pendingDeliveryCost' => $this->pendingDeliveryCost,
            'deliveryOrders' => $this->deliveryOrders,
            'availableDeliveryPeople' => $this->availableDeliveryPeople,
        ])->layout('layouts.admin');
    }
}
