<?php

namespace App\Livewire\Waiter;

use App\Livewire\Concerns\HasCart;
use App\Models\Category;
use App\Models\CustomerPoint;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use App\Models\UserAddress;
use App\Services\DeliveryNotificationService;
use App\Services\DeliveryService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\PointsService;
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
 * @property mixed $cartTotal
 * @property mixed $cartItemsCount
 * @property mixed $totalRevenue
 * @property mixed $deliveryRevenue
 * @property mixed $tableRevenue
 * @property mixed $pickupRevenue
 * @property mixed $ordersToday
 * @property mixed $deliveryOrdersToday
 * @property mixed $tableOrdersToday
 * @property mixed $pickupOrdersToday
 * @property mixed $pendingOrders
 * @property mixed $preparingOrders
 * @property mixed $activeOrders
 * @property mixed $tables
 * @property mixed $freeTables
 * @property mixed $occupiedTables
 * @property mixed $reservedTables
 * @property mixed $occupiedTablesWithOrders
 * @property mixed $availableProducts
 * @property mixed $categories
 * @property mixed $selectedProductModel
 * @property mixed $pendingOrdersCount
 * @property mixed $preparingOrdersCount
 * @property mixed $deliveringOrdersCount
 * @property mixed $readyOrdersCount
 * @property mixed $pendingDeliveryCount
 * @property mixed $waiterOccupiedTablesCount
 * @property mixed $pendingTableCount
 * @property mixed $pendingPickupCount
 * @property mixed $waiterDeliveryOrders
 * @property mixed $availableDeliveryPeople
 * @property mixed $tableStats
 * @property mixed $myOrders
 * @property mixed $myActiveOrders
 * @property mixed $orderHistory
 */
class WaiterDashboard extends Component
{
    use HasCart;

    public $tenant;

    public string $tab = 'overview';

    public string $period = 'today';

    public array $revenueData = [];

    public ?int $selectedTableId = null;

    public ?int $selectedOrderId = null;

    public ?array $orderDetail = null;

    public bool $showOrderModal = false;

    public ?int $viewingOrderId = null;

    public ?array $viewingOrder = null;

    public bool $showAddItemModal = false;

    public ?int $addItemOrderId = null;

    public ?int $addItemProductId = null;

    public int $addItemQuantity = 1;

    public ?int $selectedProduct = null;

    public ?int $orderingTableId = null;

    public ?string $orderingTableNumber = null;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $paymentMethod = '';

    public ?float $cashAmount = null;

    public string $orderType = 'mesa';

    public string $deliveryCep = '';

    public string $deliveryAddress = '';

    public string $deliveryReference = '';

    public string $deliveryCity = '';

    public string $deliveryState = '';

    public string $notes = '';

    public string $orderFilter = 'all';

    public string $waiterDeliveryFilter = 'all';

    public string $tableFilter = 'all';

    public string $historySearch = '';

    public string $historyPeriod = 'today';

    public string $historyTypeFilter = 'all';

    public string $profileName = '';

    public string $profileEmail = '';

    public string $profilePassword = '';

    public string $profilePasswordConfirmation = '';

    public bool $showPaymentModal = false;

    public ?int $paymentOrderId = null;

    public string $paymentMethodInput = 'pix';

    public float $paymentAmount = 0;

    public string $paymentNotes = '';

    public bool $showCloseTableModal = false;

    public ?int $closeTableId = null;

    public float $closeTableTotal = 0;

    public float $closeTablePending = 0;

    public string $closeTablePaymentMethod = 'pix';

    public string $closeTablePaymentNotes = '';

    public bool $showPixQrModal = false;

    public ?string $pixQrCode = null;

    public ?string $pixCopiaECola = null;

    public bool $generatingPix = false;

    public float $pixAmount = 0;

    public string $addressSearch = '';

    public array $foundAddresses = [];

    // Table Management
    public string $tableSearch = '';

    public string $tableStatusFilter = '';

    public int $editTableId = 0;

    public string $editTableNumber = '';

    public int $editTableCapacity = 4;

    public string $editTableStatus = 'free';

    public string $editTableObservation = '';

    public bool $showTableForm = false;

    public bool $showQr = false;

    public ?int $qrTableId = null;

    public ?string $qrTableNumber = null;

    public string $qrUrl = '';

    public string $qrImage = '';

    protected $listeners = [
        'orderUpdated' => '$refresh',
        'notifyNewOrder',
        'delivery-order-accepted' => 'notifyDeliveryAccepted',
        'delivery-order-picked-up' => 'notifyDeliveryPickedUp',
        'delivery-order-delivered' => 'notifyDeliveryDelivered',
    ];

    public function mount(): void
    {
        $this->tab = request()->query('tab', 'overview');
        if (Auth::check()) {
            $user = Auth::user();
            $this->customerName = $user->name;
            $this->profileName = $user->name;
            $this->profileEmail = $user->email;
            $this->tenant = $user->tenant;
        }
        $this->loadRevenueChart();
    }

    public function isStaff(): bool
    {
        return Auth::check() && Auth::user()->isStaff();
    }

    public function isCliente(): bool
    {
        return Auth::check() && Auth::user()->isCliente();
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->closeDetail();
        $this->cancelOrdering();
        $this->closeOrderModal();
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

        $orders = Order::where('tenant_id', auth()->user()->tenant_id)->where('created_at', '>=', $startDate)
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

    public function selectTable(int $tableId): void
    {
        $this->selectedTableId = $tableId;
        $this->loadOrderDetail();
    }

    public function loadOrderDetail(): void
    {
        if (! $this->selectedTableId) {
            $this->selectedOrderId = null;
            $this->orderDetail = null;

            return;
        }

        $activeOrders = Order::where('tenant_id', auth()->user()->tenant_id)->where('table_id', $this->selectedTableId)
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->with('items', 'payments')
            ->latest()
            ->get();

        if ($activeOrders->isNotEmpty()) {
            $this->selectedOrderId = $activeOrders->first()->id;
            $this->orderDetail = $activeOrders->map(fn ($order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total' => $order->total,
                'status' => $order->status,
                'type' => $order->type,
                'statusLabel' => $order->statusLabel(),
                'statusColor' => $order->statusClasses(),
                'created_at' => $order->created_at->format('d/m H:i'),
                'payment_method' => $order->payment_method,
                'nextStatus' => $order->nextStatus(),
                'nextStatusLabel' => $order->statusFlowLabels()[$order->status] ?? 'Avançar',
                'has_payment' => $order->hasPayment(),
                'pending_payment' => $order->pendingPaymentAmount(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->price * $item->quantity,
                    'cancelled_at' => $item->cancelled_at,
                    'is_cancelled' => $item->isCancelled(),
                    'is_points_item' => $item->is_points_item,
                ]),
            ])->toArray();
        } else {
            $this->selectedOrderId = null;
            $this->orderDetail = null;
        }
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        if (! $this->isStaff()) {
            return;
        }

        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $order->update(['status' => $status]);

        if ($status === 'cancelado') {
            try {
                app(PointsService::class)->reversePointsForOrder($order->fresh());
            } catch (\Throwable $e) {
                Log::error('Erro ao estornar pontos cancelamento atendente', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }

            try {
                app(PointsService::class)->refundSpentPointsForOrder($order->fresh());
            } catch (\Throwable $e) {
                Log::error('Erro ao devolver pontos gastos cancelamento atendente', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }

            if (! $order->isDelivered()) {
                try {
                    app(StockService::class)->returnOrderStock($order->fresh(), Auth::id());
                } catch (\Throwable $e) {
                    Log::error('Erro ao devolver estoque cancelamento atendente', [
                        'order_id' => $order->id, 'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($status === 'fechado') {
            try {
                app(PointsService::class)->grantPointsForOrder($order->fresh());
            } catch (\Throwable $e) {
                Log::error('Erro ao conceder pontos status atendente', [
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

        $this->loadOrderDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Status do pedido atualizado!');
    }

    public function advanceOrder(int $orderId): void
    {
        if (! $this->isStaff()) {
            return;
        }

        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $nextStatus = $order->nextStatus();

        if ($nextStatus) {
            $this->updateOrderStatus($orderId, $nextStatus);
        }
    }

    public function viewOrder(int $orderId): void
    {
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
        $this->paymentMethodInput = 'pix';
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

        $price = (float) $product->price;

        DB::transaction(function () use ($product, $order, $price) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $this->addItemQuantity,
                'price' => $price,
            ]);

            $order->increment('total', $price * $this->addItemQuantity);

            app(StockService::class)->deductStock(
                $product->id,
                $this->addItemQuantity,
                $order->tenant_id,
                $order->id,
                Auth::id(),
                'sale',
                "Adicao de item - Pedido #{$order->id}"
            );
        });

        $this->showAddItemModal = false;
        $this->addItemProductId = null;
        $this->addItemQuantity = 1;
        $this->loadOrderDetail();
        if ($this->showOrderModal && $this->viewingOrderId === $order->id) {
            $this->viewOrder($order->id);
        }
        $this->dispatch('notify', message: "{$product->name} adicionado ao pedido #{$order->id}!");
        $this->dispatch('orderUpdated');
    }

    public function removeItemFromOrder(int $itemId): void
    {
        $item = OrderItem::with('order')->findOrFail($itemId);
        $order = $item->order;

        if (! $order || $order->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada.');

            return;
        }

        $subtotal = (float) $item->price * $item->quantity;

        if (! $item->is_points_item && ! $order->isDelivered()) {
            try {
                app(StockService::class)->returnItemStock($item, Auth::id());
            } catch (\Throwable $e) {
                Log::error('Erro ao devolver estoque ao remover item', [
                    'item_id' => $item->id, 'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        $item->delete();
        $order->decrement('total', $subtotal);

        $remainingActive = $order->items()->count();
        if ($remainingActive === 0 && ! $order->isBillClosed()) {
            $order->update(['status' => 'cancelado']);
        }

        $this->loadOrderDetail();
        if ($this->showOrderModal && $this->viewingOrderId === $order->id) {
            $this->viewOrder($order->id);
        }
        $this->dispatch('notify', message: 'Item removido do pedido!');
        $this->dispatch('orderUpdated');
    }

    public function openPaymentModal(int $orderId): void
    {
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $this->paymentOrderId = $orderId;
        $this->paymentAmount = $order->pendingPaymentAmount();
        $this->paymentMethodInput = 'pix';
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
            $txid = 'pay'.$this->paymentOrderId.now()->format('YmdHis').rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData($this->tenant, $this->paymentAmount, $txid);
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixQrCode = $charge['qrcode'] ?? null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: '.$e->getMessage());
        }

        $this->generatingPix = false;
    }

    public function registerPayment(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethodInput' => 'required|string',
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
            'payment_method' => $this->paymentMethodInput,
            'status' => 'paid',
            'paid_at' => now(),
            'notes' => $this->paymentNotes,
        ]);

        if ($order->pendingPaymentAmount() <= 0) {
            $order->update([
                'status' => 'fechado',
                'bill_closed_at' => now(),
            ]);
        }

        try {
            app(PointsService::class)->grantPointsForOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Erro ao conceder pontos pagamento atendente', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
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

        $this->loadOrderDetail();
        $this->dispatch('notify', message: 'Pagamento registrado com sucesso!');
        $this->dispatch('orderUpdated');
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->paymentOrderId = null;
        $this->paymentMethodInput = 'pix';
        $this->paymentAmount = 0;
        $this->paymentNotes = '';
    }

    public function openCloseTableModal(int $tableId): void
    {
        $table = Table::where('tenant_id', auth()->user()->tenant_id)->with(['orders' => function ($q) {
            $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
                ->where('status', '!=', 'fechado');
        }])->findOrFail($tableId);

        $total = 0;
        $pending = 0;
        foreach ($table->orders as $order) {
            if (! $order->isBillClosed()) {
                $total += (float) $order->total;
                $pending += $order->pendingPaymentAmount();
            }
        }

        $this->closeTableId = $tableId;
        $this->closeTableTotal = $total;
        $this->closeTablePending = $pending;
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
            $txid = 'mesa'.$this->closeTableId.now()->format('YmdHis').rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData($this->tenant, $this->closeTableTotal, $txid);
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixQrCode = $charge['qrcode'] ?? null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: '.$e->getMessage());
        }

        $this->generatingPix = false;
    }

    public function confirmCloseTableBill(): void
    {
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
                    Log::error('Erro ao conceder pontos fechamento mesa atendente', [
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
            $this->dispatch('tableFreed')->to('public.menu');
            $this->dispatch('tableFreed')->to('public.cart');
            $this->dispatch('notify', message: "Conta da Mesa {$table->number} fechada! R$ ".number_format($totalPending, 2, ',', '.')." em {$closedCount} pedido(s). Pagamento: PIX");
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

    public function closeCloseTableModal(): void
    {
        $this->showCloseTableModal = false;
        $this->closeTableId = null;
        $this->closeTableTotal = 0;
        $this->closeTablePaymentMethod = 'pix';
        $this->closeTablePaymentNotes = '';
    }

    public function closeBill(int $orderId): void
    {
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
            Log::error('Erro ao conceder pontos fechar conta atendente', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
        }

        $this->closeOrderModal();
        $this->loadOrderDetail();
        $this->dispatch('notify', message: "Conta do pedido #{$order->id} fechada!");
        $this->dispatch('orderUpdated');
    }

    public function reopenAccount(int $orderId): void
    {
        if (! Auth::user()?->isAdmin()) {
            $this->dispatch('notify', message: 'Apenas administradores podem reabrir contas.');

            return;
        }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        if ($order->status !== 'fechado') {
            $this->dispatch('notify', message: 'Apenas contas fechadas podem ser reabertas.');

            return;
        }
        $order->update(['status' => 'entregue', 'bill_closed_at' => null]);

        try {
            app(PointsService::class)->refundSpentPointsForOrder($order);
        } catch (\Throwable $e) {
            Log::error('Erro ao devolver pontos na reabertura atendente', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($order->table_id) {
            $order->table()->update(['status' => 'occupied']);
        }

        if ($this->showOrderModal && $this->viewingOrderId === $orderId) {
            $this->viewOrder($orderId);
        }
        $this->dispatch('notify', message: "Conta #{$order->id} reaberta!");
        $this->dispatch('orderUpdated');
    }

    public function finalizeOrder(): void
    {
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

    public function freeTable(int $tableId): void
    {
        if (! $this->isStaff()) {
            return;
        }

        $table = Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($tableId);

        $activeOrders = Order::where('tenant_id', auth()->user()->tenant_id)->where('table_id', $tableId)
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->get();

        foreach ($activeOrders as $activeOrder) {
            if (! $activeOrder->hasPayment() || $activeOrder->pendingPaymentAmount() <= 0) {
                $activeOrder->update([
                    'status' => 'fechado',
                    'bill_closed_at' => now(),
                ]);
                try {
                    app(PointsService::class)->grantPointsForOrder($activeOrder->fresh());
                } catch (\Throwable $e) {
                    Log::error('Erro ao conceder pontos liberar mesa atendente', [
                        'order_id' => $activeOrder->id, 'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $activeOrder->update(['status' => 'entregue']);
            }
        }

        $table->update(['status' => 'free']);

        $this->dispatch('tableFreed')->to('public.menu');
        $this->dispatch('tableFreed')->to('public.cart');
        $this->closeDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa '.$table->number.' liberada!');
    }

    public function setTableReserved(int $tableId): void
    {
        if (! $this->isStaff()) {
            return;
        }
        Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($tableId)->update(['status' => 'reserved']);
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa reservada!');
    }

    public function setTableOccupied(int $tableId): void
    {
        if (! $this->isStaff()) {
            return;
        }
        Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($tableId)->update(['status' => 'occupied']);
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa ocupada!');
    }

    public function setTableFree(int $tableId): void
    {
        if (! $this->isStaff()) {
            return;
        }

        $table = Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($tableId);

        $activeOrders = Order::where('tenant_id', auth()->user()->tenant_id)->where('table_id', $tableId)
            ->whereNotIn('status', ['fechado', 'cancelado'])
            ->exists();

        if ($activeOrders) {
            $this->dispatch('notify', message: 'Use "Fechar Conta" ou "Liberar Mesa" para mesas com pedidos ativos.');

            return;
        }

        $table->update(['status' => 'free']);
        $this->dispatch('tableFreed')->to('public.menu');
        $this->dispatch('tableFreed')->to('public.cart');
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa liberada!');
    }

    public function closeDetail(): void
    {
        $this->selectedTableId = null;
        $this->selectedOrderId = null;
        $this->orderDetail = null;
    }

    public function startOrdering(int $tableId, string $tableNumber): void
    {
        $this->orderingTableId = $tableId;
        $this->orderingTableNumber = $tableNumber;
        $this->orderType = 'mesa';
        $this->deliveryCep = '';
        $this->deliveryAddress = '';
        $this->deliveryReference = '';
        $this->resetCart();
        $this->selectedProduct = null;
        $this->notes = '';
        $this->paymentMethod = '';
        $this->cashAmount = null;
    }

    public function startDeliveryOrder(): void
    {
        $this->orderingTableId = null;
        $this->orderingTableNumber = null;
        $this->orderType = 'entrega';
        $this->deliveryCep = '';
        $this->deliveryAddress = '';
        $this->deliveryReference = '';
        $this->resetCart();
        $this->selectedProduct = null;
        $this->notes = '';
        $this->paymentMethod = 'pix';
        $this->cashAmount = null;
    }

    public function startPickupOrder(): void
    {
        $this->orderingTableId = null;
        $this->orderingTableNumber = null;
        $this->orderType = 'retirada';
        $this->deliveryCep = '';
        $this->deliveryAddress = '';
        $this->deliveryReference = '';
        $this->resetCart();
        $this->selectedProduct = null;
        $this->notes = '';
        $this->paymentMethod = '';
        $this->cashAmount = null;
    }

    public function cancelOrdering(): void
    {
        $this->orderingTableId = null;
        $this->orderingTableNumber = null;
        $this->orderType = '';
        $this->deliveryCep = '';
        $this->deliveryAddress = '';
        $this->deliveryReference = '';
        $this->resetCart();
        $this->selectedProduct = null;
        $this->paymentMethod = '';
        $this->cashAmount = null;
    }

    public function showProduct(int $productId): void
    {
        $this->selectedProduct = $productId;
    }

    public function closeProduct(): void
    {
        $this->selectedProduct = null;
    }

    public function addToCart(int $productId, string $productName, float $price, array $options = [], int $quantity = 1): void
    {
        $this->addCartItem($productId, $productName, $price, $options, $quantity);
        $this->dispatch('notify', message: "{$productName} adicionado ao pedido!");
    }

    public function removeFromCart(string $key): void
    {
        $this->removeCartItem($key);
    }

    public function updateCartQuantity(string $key, int $delta): void
    {
        $this->adjustCartQuantity($key, $delta);
    }

    #[Computed]
    public function cartTotal(): float
    {
        return $this->calcCartTotal();
    }

    #[Computed]
    public function cartItemsCount(): int
    {
        return $this->calcCartItemsCount();
    }

    public function placeOrder(): void
    {
        $this->validate([
            'customerName' => 'required|string|max:255',
        ]);

        $this->revalidateCartAgainstDatabase();

        if (empty($this->cartItems)) {
            return;
        }

        if ($this->orderType === 'entrega' && ! $this->paymentMethod) {
            $this->dispatch('notify', message: 'Selecione a forma de pagamento.');

            return;
        }

        $deliveryCost = 0;
        if ($this->orderType === 'entrega' && $this->deliveryAddress) {
            $validation = app(DeliveryService::class)->validateDeliveryAddress(
                $this->tenant,
                $this->deliveryAddress,
                $this->deliveryCity ?: $this->tenant->city,
                $this->deliveryState ?: $this->tenant->state,
                $this->deliveryCep ?: null
            );
            if (! $validation['valid']) {
                $this->dispatch('notify', message: $validation['error']);

                return;
            }
            $deliveryCost = $this->tenant->deliveryCostForDistance($validation['distance'] ?? null);
        }

        if ($this->paymentMethod === 'cash' && (! $this->cashAmount || $this->cashAmount <= 0)) {
            $this->dispatch('notify', message: 'Informe o valor para calculo do troco.');

            return;
        }

        if ($this->paymentMethod === 'cash' && $this->cashAmount < $this->cartTotal + $deliveryCost) {
            $this->dispatch('notify', message: 'O valor informado deve ser maior ou igual ao total do pedido.');

            return;
        }

        $tableId = $this->orderingTableId;

        if ($this->orderType === 'mesa' && ! $tableId) {
            $table = Table::where('tenant_id', $this->tenant->id)
                ->where('status', 'free')
                ->orderByRaw('CAST(number AS UNSIGNED), number')
                ->first();

            if ($table) {
                $tableId = $table->id;
                $this->orderingTableNumber = $table->number;
            }
        }

        $stockErrors = app(StockService::class)->validateStockForCartItems($this->cartItems, $this->tenant->id);
        if (! empty($stockErrors)) {
            foreach ($stockErrors as $error) {
                $this->dispatch('notify', message: $error);
            }

            return;
        }

        $orderId = null;

        DB::transaction(function () use ($tableId, $deliveryCost, &$orderId) {
            if ($tableId) {
                Table::where('id', $tableId)->update(['status' => 'occupied']);
            }

            $addressData = null;
            if ($this->orderType === 'entrega' && $this->deliveryAddress) {
                $addressData = [
                    'zipcode' => $this->deliveryCep,
                    'address' => $this->deliveryAddress,
                    'reference' => $this->deliveryReference,
                    'city' => $this->deliveryCity ?: '',
                    'state' => $this->deliveryState ?: '',
                ];
            }

            $order = Order::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => Auth::id(),
                'table_id' => $tableId,
                'customer_name' => $this->customerName,
                'customer_phone' => $this->customerPhone,
                'total' => $this->cartTotal + $deliveryCost,
                'payment_method' => $this->orderType === 'entrega' ? $this->paymentMethod : null,
                'payment_change' => $this->paymentMethod === 'cash' ? $this->cashAmount : null,
                'status' => 'novo',
                'type' => $this->orderType,
                'address_json' => $addressData,
                'delivery_cost' => $deliveryCost > 0 ? $deliveryCost : null,
                'notes' => $this->notes,
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                    'selected_options_json' => $item['options'],
                ]);
            }

            app(StockService::class)->deductOrderStock($order, Auth::id());

            $orderId = $order->id;
        });

        if ($this->orderType === 'entrega' && $order = Order::find($orderId)) {
            $this->dispatch('delivery-new-order-available');
            app(DeliveryNotificationService::class)->newOrderAvailable($order);
        }

        $this->cancelOrdering();
        $this->dispatch('orderUpdated');
        $this->dispatch('notifyNewOrder');
        $this->dispatch('notify', message: "Pedido #{$orderId} criado com sucesso!");
    }

    public function saveProfile(): void
    {
        $this->validate([
            'profileName' => 'required|string|max:255',
            'profileEmail' => 'required|email|max:255',
            'profilePassword' => 'nullable|string|min:6',
            'profilePasswordConfirmation' => 'nullable|same:profilePassword',
        ]);

        $user = Auth::user();
        $data = ['name' => $this->profileName, 'email' => $this->profileEmail];
        if ($this->profilePassword) {
            $data['password'] = $this->profilePassword;
        }
        $user->update($data);
        $this->profilePassword = '';
        $this->profilePasswordConfirmation = '';
        $this->dispatch('notify', message: 'Perfil atualizado com sucesso!');
    }

    public function updatedAddressSearch(): void
    {
        $this->searchAddresses();
    }

    public function searchAddresses(): void
    {
        $this->foundAddresses = [];

        if (strlen($this->addressSearch) < 3) {
            return;
        }

        $this->foundAddresses = UserAddress::whereHas('user', function ($q) {
            $q->where('tenant_id', $this->tenant->id)
                ->where(function ($sq) {
                    $sq->where('name', 'like', "%{$this->addressSearch}%")
                        ->orWhere('email', 'like', "%{$this->addressSearch}%");
                });
        })->with('user')->limit(5)->get()->toArray();
    }

    public function selectDeliveryAddress(int $addressId): void
    {
        $address = UserAddress::with('user')->find($addressId);
        if ($address) {
            $this->deliveryAddress = $address->full_address;
            $this->deliveryReference = $address->reference ?? '';
            $this->deliveryCep = $address->zipcode ?? '';
            $this->deliveryCity = $address->city ?? '';
            $this->deliveryState = $address->state ?? '';
            $this->customerName = $address->user->name;
            $this->foundAddresses = [];
            $this->addressSearch = '';
        }
    }

    public function notifyNewOrder(): void
    {
        $latest = Order::where('tenant_id', auth()->user()->tenant_id)->with('table')->where('status', 'novo')->latest()->first();
        $tableInfo = $latest?->table ? " na Mesa {$latest->table->number}" : '';
        $itemCount = $latest?->items->count() ?? 0;
        $extra = $itemCount > 0 ? " ({$itemCount} itens)" : '';
        $this->dispatch('notify', message: "Novo pedido{$tableInfo}!{$extra}");
    }

    public function notifyDeliveryAccepted(array $params): void
    {
        $name = $params['deliveryName'] ?? '';
        $this->dispatch('notify', message: "Pedido #{$params['orderId']} aceito por {$name}");
    }

    public function notifyDeliveryPickedUp(array $params): void
    {
        $name = $params['deliveryName'] ?? '';
        $this->dispatch('notify', message: "{$name} saiu para entrega do pedido #{$params['orderId']}");
    }

    public function notifyDeliveryDelivered(array $params): void
    {
        $name = $params['deliveryName'] ?? '';
        $this->dispatch('notify', message: "Pedido #{$params['orderId']} entregue por {$name}");
    }

    #[Computed]
    public function totalRevenue()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])->sum('total');
    }

    #[Computed]
    public function deliveryRevenue()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->where('type', 'entrega')
            ->sum('total');
    }

    #[Computed]
    public function tableRevenue()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->where('type', 'mesa')
            ->sum('total');
    }

    #[Computed]
    public function pickupRevenue()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['entregue', 'saiu_entrega', 'fechado'])
            ->where('type', 'retirada')
            ->sum('total');
    }

    #[Computed]
    public function ordersToday()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereDate('created_at', Carbon::today())->count();
    }

    #[Computed]
    public function deliveryOrdersToday()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereDate('created_at', Carbon::today())
            ->where('type', 'entrega')
            ->count();
    }

    #[Computed]
    public function tableOrdersToday()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereDate('created_at', Carbon::today())
            ->where('type', 'mesa')
            ->count();
    }

    #[Computed]
    public function pickupOrdersToday()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereDate('created_at', Carbon::today())
            ->where('type', 'retirada')
            ->count();
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
    public function tables()
    {
        return $this->tenant->manageableTables()->with('tenant')->withCount(['orders' => function ($q) {
            $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue']);
        }])->get();
    }

    #[Computed]
    public function freeTables()
    {
        return $this->tables->where('status', 'free');
    }

    #[Computed]
    public function occupiedTables()
    {
        return $this->tables->where('status', 'occupied');
    }

    #[Computed]
    public function reservedTables()
    {
        return $this->tables->where('status', 'reserved');
    }

    #[Computed]
    public function occupiedTablesWithOrders()
    {
        return Table::where('tenant_id', auth()->user()->tenant_id)->where('status', 'occupied')
            ->withCount(['orders' => function ($q) {
                $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue']);
            }])
            ->orderByRaw('CAST(number AS UNSIGNED), number')
            ->get();
    }

    #[Computed]
    public function availableProducts()
    {
        return Product::where('tenant_id', auth()->user()->tenant_id)->active()->with('category')->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('tenant_id', $this->tenant->id)
            ->with(['products' => function ($q) {
                $q->active()->with('attributes.options');
            }])
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function selectedProductModel()
    {
        if (! $this->selectedProduct) {
            return null;
        }

        return Product::with('attributes.options')
            ->where('tenant_id', $this->tenant->id)
            ->find($this->selectedProduct);
    }

    #[Computed]
    public function pendingOrdersCount()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'novo')->count();
    }

    #[Computed]
    public function preparingOrdersCount()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'em_preparo')->count();
    }

    #[Computed]
    public function deliveringOrdersCount()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'saiu_entrega')->count();
    }

    #[Computed]
    public function readyOrdersCount()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'pronto')->count();
    }

    #[Computed]
    public function pendingDeliveryCount()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'novo')
            ->where('type', 'entrega')
            ->count();
    }

    #[Computed]
    public function waiterOccupiedTablesCount()
    {
        return $this->tenant->manageableTables()->where('status', 'occupied')->count();
    }

    #[Computed]
    public function pendingTableCount()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'novo')
            ->where('type', 'mesa')
            ->count();
    }

    #[Computed]
    public function pendingPickupCount()
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->where('status', 'novo')
            ->where('type', 'retirada')
            ->count();
    }

    #[Computed]
    public function waiterDeliveryOrders()
    {
        $query = Order::where('tenant_id', auth()->user()->tenant_id)->where('type', 'entrega')
            ->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega', 'entregue'])
            ->with('items', 'table', 'deliveryPerson', 'payments');

        if ($this->waiterDeliveryFilter === 'pending') {
            $query->whereIn('status', ['novo', 'em_preparo']);
        } elseif ($this->waiterDeliveryFilter === 'in_transit') {
            $query->where('status', 'saiu_entrega');
        } elseif ($this->waiterDeliveryFilter === 'delivered') {
            $query->where('status', 'entregue');
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
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $order->update(['delivery_person_id' => null]);

        $this->dispatch('notify', message: "Entregador removido do pedido #{$order->id}!");
        $this->dispatch('orderUpdated');
    }

    #[Computed]
    public function tableStats()
    {
        $q = $this->tenant->manageableTables();
        $total = (clone $q)->count();
        $free = (clone $q)->where('status', 'free')->count();
        $occupied = (clone $q)->where('status', 'occupied')->count();
        $reserved = (clone $q)->where('status', 'reserved')->count();

        return compact('total', 'free', 'occupied', 'reserved');
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
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega'])
            ->with('items', 'table')
            ->latest()
            ->get();
    }

    #[Computed]
    public function orderHistory()
    {
        $query = Order::where('tenant_id', auth()->user()->tenant_id)->with('items', 'table');

        if ($this->isCliente()) {
            $query->where('user_id', Auth::id());
        }

        $query->whereIn('status', ['entregue', 'cancelado', 'fechado']);

        if ($this->historyPeriod === 'today') {
            $query->whereDate('created_at', now()->today());
        } elseif ($this->historyPeriod === 'week') {
            $query->whereDate('created_at', '>=', now()->subDays(7));
        } elseif ($this->historyPeriod === 'month') {
            $query->whereDate('created_at', '>=', now()->subDays(30));
        }

        if ($this->historySearch) {
            if ($this->isStaff()) {
                $query->where(function ($q) {
                    $q->where('customer_name', 'like', "%{$this->historySearch}%")
                        ->orWhere('id', 'like', "%{$this->historySearch}%");
                });
            }
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
            'items' => $order->items,
            'address_json' => $order->address_json,
            'order_count' => 1,
            'is_grouped' => false,
            'table_id' => $order->table_id,
        ];
    }

    // Table Management
    public function editTable(int $id): void
    {
        $table = Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->editTableId = $table->id;
        $this->editTableNumber = $table->number;
        $this->editTableCapacity = $table->capacity;
        $this->editTableStatus = $table->status;
        $this->editTableObservation = $table->observation ?? '';
        $this->showTableForm = true;
    }

    public function saveTable(): void
    {
        $this->validate([
            'editTableNumber' => 'required|string|max:20',
            'editTableCapacity' => 'required|integer|min:1|max:50',
            'editTableStatus' => 'required|in:free,occupied,reserved',
            'editTableObservation' => 'nullable|string|max:500',
        ]);

        $existing = Table::where('number', $this->editTableNumber)
            ->where('id', '!=', $this->editTableId)
            ->first();

        if ($existing) {
            $this->addError('editTableNumber', 'Ja existe uma mesa com este numero.');

            return;
        }

        Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editTableId)->update([
            'number' => $this->editTableNumber,
            'capacity' => $this->editTableCapacity,
            'status' => $this->editTableStatus,
            'observation' => $this->editTableObservation ?: null,
        ]);
        $this->dispatch('notify', message: 'Mesa '.$this->editTableNumber.' atualizada!');
        $this->resetTableForm();
    }

    public function resetTableForm(): void
    {
        $this->showTableForm = false;
        $this->editTableId = 0;
        $this->editTableNumber = '';
        $this->editTableCapacity = 4;
        $this->editTableStatus = 'free';
        $this->editTableObservation = '';
    }

    public function toggleTableStatus(int $id): void
    {
        $table = Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $newStatus = match ($table->status) {
            'free' => 'occupied',
            'occupied' => 'reserved',
            'reserved' => 'free',
            default => 'free',
        };

        if ($newStatus === 'free') {
            $activeOrders = Order::where('tenant_id', auth()->user()->tenant_id)->where('table_id', $id)
                ->whereNotIn('status', ['fechado', 'cancelado'])
                ->exists();

            if ($activeOrders) {
                $this->dispatch('notify', message: 'Use "Fechar Conta" ou "Liberar Mesa" para mesas com pedidos ativos.');

                return;
            }
        }

        $table->update(['status' => $newStatus]);

        if ($newStatus === 'free') {
            $this->dispatch('tableFreed')->to('public.menu');
            $this->dispatch('tableFreed')->to('public.cart');
        }

        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa alterada para '.match ($newStatus) {
            'free' => 'Livre', 'occupied' => 'Ocupada', 'reserved' => 'Reservada'
        });
    }

    public function showTableQrCode(int $id): void
    {
        $table = Table::where('tenant_id', auth()->user()->tenant_id)->with('tenant')->findOrFail($id);
        $this->qrTableId = $id;
        $this->qrTableNumber = $table->number;
        $this->qrUrl = route('menu.show', [
            'slug' => $table->tenant->slug,
            'token' => $table->token,
        ]);
        $this->qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='.urlencode($this->qrUrl);
        $this->showQr = true;
    }

    public function closeQrCode(): void
    {
        $this->showQr = false;
        $this->qrTableId = null;
        $this->qrTableNumber = null;
        $this->qrUrl = '';
        $this->qrImage = '';
    }

    public function render()
    {
        return view('livewire.waiter.waiter-dashboard', [
            'tables' => $this->tables,
            'freeTables' => $this->freeTables,
            'occupiedTables' => $this->occupiedTables,
            'reservedTables' => $this->reservedTables,
            'categories' => $this->categories,
            'selectedProductModel' => $this->selectedProductModel,
            'activeOrders' => $this->activeOrders,
            'pendingOrdersCount' => $this->pendingOrdersCount,
            'preparingOrdersCount' => $this->preparingOrdersCount,
            'deliveringOrdersCount' => $this->deliveringOrdersCount,
            'readyOrdersCount' => $this->readyOrdersCount,
            'pendingDeliveryCount' => $this->pendingDeliveryCount,
            'pendingTableCount' => $this->pendingTableCount,
            'pendingPickupCount' => $this->pendingPickupCount,
            'tableStats' => $this->tableStats,
            'orderHistory' => $this->orderHistory,
            'cartTotal' => $this->cartTotal,
            'cartItemsCount' => $this->cartItemsCount,
            'myOrders' => $this->myOrders,
            'myActiveOrders' => $this->myActiveOrders,
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
            'occupiedTablesWithOrders' => $this->occupiedTablesWithOrders,
            'availableProducts' => $this->availableProducts,
            'waiterDeliveryOrders' => $this->waiterDeliveryOrders,
            'availableDeliveryPeople' => $this->availableDeliveryPeople,
            'waiterOccupiedTablesCount' => $this->waiterOccupiedTablesCount,
        ])->extends('layouts.waiter');
    }
}
