<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasCart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Services\DeliveryNotificationService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\PointsService;
use App\Services\StockService;

class TableGrid extends Component
{
    use HasCart;

    public $tenant;

    public string $filter = 'all';

    public ?int $selectedTableId = null;

    public ?int $selectedOrderId = null;

    public ?array $orderDetail = null;

    public bool $showPaymentModal = false;
    public ?int $paymentOrderId = null;
    public string $paymentMethod = 'pix';
    public float $paymentAmount = 0;
    public string $paymentNotes = '';

    public bool $showAddItemModal = false;
    public ?int $addItemOrderId = null;
    public ?int $addItemProductId = null;
    public int $addItemQuantity = 1;

    public bool $showCloseTableModal = false;
    public ?int $closeTableId = null;
    public float $closeTableTotal = 0;
    public string $closeTablePaymentMethod = 'pix';
    public string $closeTablePaymentNotes = '';

    public bool $showPixQrModal = false;
    public ?string $pixQrCode = null;
    public ?string $pixCopiaECola = null;
    public bool $generatingPix = false;
    public string $pixAction = '';
    public float $pixAmount = 0;

    public ?int $orderingTableId = null;
    public ?string $orderingTableNumber = null;
    public ?int $selectedProduct = null;
    public string $customerName = '';
    public string $customerPhone = '';
    public string $orderPaymentMethod = '';
    public ?float $cashAmount = null;
    public string $orderType = 'mesa';
    public string $deliveryCep = '';
    public string $deliveryAddress = '';
    public string $deliveryReference = '';
    public string $deliveryCity = '';
    public string $deliveryState = '';
    public string $notes = '';
    public string $addressSearch = '';
    public array $foundAddresses = [];

    protected $listeners = [
        'orderUpdated' => '$refresh',
        'notifyNewOrder',
        'delivery-order-accepted' => 'notifyDeliveryAccepted',
        'delivery-order-picked-up' => 'notifyDeliveryPickedUp',
        'delivery-order-delivered' => 'notifyDeliveryDelivered',
    ];

    public function mount(): void
    {
        $this->tenant = Auth::user()->tenant;
        $this->restoreCartFromSession();
    }

    public function notifyNewOrder(): void
    {
        $this->dispatch('notify', message: 'Novo pedido recebido!');
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

    public function selectTable(int $tableId): void
    {
        $this->selectedTableId = $tableId;
        $this->loadOrderDetail();
    }

    public function loadOrderDetail(): void
    {
        if (!$this->selectedTableId) {
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
            $ordersData = $activeOrders->map(fn($order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total' => $order->total,
                'status' => $order->status,
                'type' => $order->type,
                'typeLabel' => $order->typeLabel(),
                'typeClasses' => $order->typeClasses(),
                'statusLabel' => $order->statusLabel(),
                'statusColor' => $order->statusClasses(),
                'created_at' => $order->created_at->format('d/m H:i'),
                'nextStatus' => $order->nextStatus(),
                'nextStatusLabel' => $order->statusFlowLabels()[$order->status] ?? 'Avançar',
                'has_payment' => $order->hasPayment(),
                'pending_payment' => $order->pendingPaymentAmount(),
                'items' => $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->price * $item->quantity,
                    'cancelled_at' => $item->cancelled_at,
                    'is_cancelled' => $item->isCancelled(),
                    'is_points_item' => $item->is_points_item,
                ]),
            ]);
            $grouped = $ordersData->groupBy('customer_name');
            $this->orderDetail = $grouped->map(fn($orders) => [
                'customer_name' => $orders->first()['customer_name'],
                'customer_phone' => $orders->first()['customer_phone'],
                'total' => $orders->sum('total'),
                'orders' => $orders->toArray(),
                'has_payment' => $orders->contains('has_payment', true),
            ])->values()->toArray();
        } else {
            $this->selectedOrderId = null;
            $this->orderDetail = null;
        }
    }

    public function advanceOrder(int $orderId): void
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $nextStatus = $order->nextStatus();

        if ($nextStatus) {
            $this->updateOrderStatus($orderId, $nextStatus);
        }
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
        $order = Order::where('tenant_id', auth()->user()->tenant_id)->findOrFail($orderId);
        $order->update(['status' => $status]);

        if ($order->table_id && $status === 'novo') {
            $order->table()->update(['status' => 'occupied']);
        }

        if ($order->table_id && $status === 'fechado') {
            $wasFreed = \App\Models\Table::tryFreeTable($order->table_id);
            if ($wasFreed) {
                $this->dispatch('tableFreed')->to('public.menu');
                $this->dispatch('tableFreed')->to('public.cart');
            }
        }

        if ($status === 'fechado') {
            app(PointsService::class)->grantPointsForOrder($order->fresh());
        }

        if ($status === 'cancelado') {
            app(PointsService::class)->reversePointsForOrder($order->fresh());
            app(PointsService::class)->refundSpentPointsForOrder($order->fresh());
            if (!$order->isDelivered()) {
                try {
                    app(StockService::class)->returnOrderStock($order->fresh(), auth()->id());
                } catch (\Throwable $e) {
                    Log::error('Erro ao devolver estoque no cancelamento', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->loadOrderDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Status do pedido atualizado!');
    }

    public function cancelItem(int $itemId): void
    {
        if (!auth()->user()->isAdmin()) { abort(403); }

        $item = OrderItem::with('order')->findOrFail($itemId);
        $order = $item->order;

        if (!$order || $order->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($item->isCancelled()) {
            $this->dispatch('notify', message: 'Item ja cancelado.');
            return;
        }

        if ($order->isBillClosed()) {
            $this->dispatch('notify', message: 'Conta ja fechada, nao e possivel cancelar itens.');
            return;
        }

        if ($order->isDelivered() && !$order->isCancelled()) {
            $this->dispatch('notify', message: 'Pedido ja entregue. Estoque nao sera devolvido automaticamente. Se necessario, ajuste manualmente.');
        }

        $deduction = (float) $item->price * (int) $item->quantity;

        $item->update([
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
        ]);

        $order->decrement('total', $deduction);

        if (!$order->isDelivered()) {
            try {
                app(StockService::class)->returnItemStock($item, Auth::id());
            } catch (\Throwable $e) {
                Log::error('Erro ao devolver estoque por item cancelado', [
                    'item_id' => $item->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($item->is_points_item && $item->points_cost) {
            try {
                app(PointsService::class)->refundPointsForItem($item);
                $order->decrement('points_spent', (int) $item->points_cost);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Erro ao devolver pontos por item cancelado', [
                    'item_id' => $item->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $remainingActive = $order->items()->whereNull('cancelled_at')->count();
        if ($remainingActive === 0 && !$order->isBillClosed()) {
            $order->update(['status' => 'cancelado']);
            app(PointsService::class)->reversePointsForOrder($order->fresh());
            app(PointsService::class)->refundSpentPointsForOrder($order->fresh());
        }

        $this->loadOrderDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Item removido do pedido!');
    }

    public function openPaymentModal(int $orderId): void
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
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
            $txid = 'pay' . $this->paymentOrderId . now()->format('YmdHis') . rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData($this->tenant, $this->paymentAmount, $txid);
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixQrCode = $charge['qrcode'] ?? null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: ' . $e->getMessage());
        }

        $this->generatingPix = false;
    }

    public function registerPayment(): void
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
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

        if ($order->pendingPaymentAmount() <= 0) {
            $order->update([
                'status' => 'fechado',
                'bill_closed_at' => now(),
            ]);
            app(PointsService::class)->grantPointsForOrder($order->fresh());
        }

        $this->showPaymentModal = false;
        $this->paymentOrderId = null;
        $this->paymentAmount = 0;
        $this->paymentNotes = '';
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;

        $this->loadOrderDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Pagamento registrado com sucesso!');
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->paymentOrderId = null;
        $this->paymentMethod = 'pix';
        $this->paymentAmount = 0;
        $this->paymentNotes = '';
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
        if (!auth()->user()->isAdmin()) { abort(403); }
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
            $item = OrderItem::create([
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
        $this->dispatch('notify', message: "{$product->name} adicionado ao pedido #{$order->id}!");
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
            if (!$order->isBillClosed()) {
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
            $txid = 'mesa' . $this->closeTableId . now()->format('YmdHis') . rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData($this->tenant, $this->closeTableTotal, $txid);
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixQrCode = $charge['qrcode'] ?? null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: ' . $e->getMessage());
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
            if (!$order->isBillClosed()) {
                $totalPending += $order->pendingPaymentAmount();
                $order->update([
                    'status' => 'fechado',
                    'bill_closed_at' => now(),
                ]);
                app(PointsService::class)->grantPointsForOrder($order->fresh());
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
            $this->dispatch('tableFreed')->to('public.menu');
            $this->dispatch('tableFreed')->to('public.cart');
            $this->dispatch('notify', message: "Conta da Mesa {$table->number} fechada! R$ " . number_format($totalPending, 2, ',', '.') . " em {$closedCount} pedido(s). Pagamento: " . ($this->closeTablePaymentMethod === 'pix' ? 'PIX' : ($this->closeTablePaymentMethod === 'credit_card' ? 'Cartao Credito' : ($this->closeTablePaymentMethod === 'debit_card' ? 'Cartao Debito' : 'Dinheiro'))));
        } else {
            $this->dispatch('notify', message: "Nenhum pedido da Mesa {$table->number} pode ser fechado.");
        }

        $this->closeDetail();
        $this->dispatch('orderUpdated');
    }

    public function closeCloseTableModal(): void
    {
        $this->showCloseTableModal = false;
        $this->closeTableId = null;
        $this->closeTableTotal = 0;
        $this->closeTablePaymentMethod = 'pix';
        $this->closeTablePaymentNotes = '';
    }

    public function freeTable(int $tableId): void
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
        $table = Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($tableId);

        $activeOrders = Order::where('tenant_id', auth()->user()->tenant_id)->where('table_id', $tableId)
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->get();

        foreach ($activeOrders as $activeOrder) {
            if (!$activeOrder->hasPayment() || $activeOrder->pendingPaymentAmount() <= 0) {
                $activeOrder->update([
                    'status' => 'fechado',
                    'bill_closed_at' => now(),
                ]);
                app(PointsService::class)->grantPointsForOrder($activeOrder->fresh());
            } else {
                $activeOrder->update(['status' => 'entregue']);
            }
        }

        $table->update(['status' => 'free']);

        $this->dispatch('tableFreed')->to('public.menu');
        $this->dispatch('tableFreed')->to('public.cart');
        $this->closeDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa ' . $table->number . ' liberada!');
    }

    public function setTableReserved(int $tableId): void
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
        Table::where('tenant_id', auth()->user()->tenant_id)->findOrFail($tableId)->update(['status' => 'reserved']);
        $this->dispatch('tableFreed')->to('public.menu');
        $this->dispatch('tableFreed')->to('public.cart');
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa reservada!');
    }

    public function closePixQrModal(): void
    {
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
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
        $this->orderPaymentMethod = '';
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
        $this->orderPaymentMethod = 'pix';
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
        $this->orderPaymentMethod = '';
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
        $this->orderPaymentMethod = '';
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
        if (!$this->selectedProduct) return null;
        return Product::where('tenant_id', auth()->user()->tenant_id)->with('attributes.options')->find($this->selectedProduct);
    }

    #[Computed]
    public function availableProducts()
    {
        return Product::where('tenant_id', auth()->user()->tenant_id)->active()->with('category')->orderBy('name')->get();
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

        if ($this->orderType === 'entrega' && !$this->orderPaymentMethod) {
            $this->dispatch('notify', message: 'Selecione a forma de pagamento.');
            return;
        }

        $deliveryCost = 0;
        if ($this->orderType === 'entrega' && $this->deliveryAddress) {
            $validation = app(\App\Services\DeliveryService::class)->validateDeliveryAddress(
                $this->tenant,
                $this->deliveryAddress,
                $this->deliveryCity ?: $this->tenant->city,
                $this->deliveryState ?: $this->tenant->state,
                $this->deliveryCep ?: null
            );
            if (!$validation['valid']) {
                $this->dispatch('notify', message: $validation['error']);
                return;
            }
            $deliveryCost = $this->tenant->deliveryCostForDistance($validation['distance'] ?? null);
        }

        if ($this->orderPaymentMethod === 'cash' && (!$this->cashAmount || $this->cashAmount <= 0)) {
            $this->dispatch('notify', message: 'Informe o valor para calculo do troco.');
            return;
        }

        if ($this->orderPaymentMethod === 'cash' && $this->cashAmount < $this->cartTotal + $deliveryCost) {
            $this->dispatch('notify', message: 'O valor informado deve ser maior ou igual ao total do pedido.');
            return;
        }

        $tableId = $this->orderingTableId;

        if ($this->orderType === 'mesa' && !$tableId) {
            $table = Table::where('tenant_id', $this->tenant->id)
                ->where('status', 'free')
                ->orderByRaw("CAST(number AS UNSIGNED), number")
                ->first();

            if ($table) {
                $tableId = $table->id;
                $this->orderingTableNumber = $table->number;
            }
        }

        $stockErrors = app(StockService::class)->validateStockForCartItems($this->cartItems, $this->tenant->id);
        if (!empty($stockErrors)) {
            foreach ($stockErrors as $error) {
                $this->dispatch('notify', message: $error);
            }
            return;
        }

        $orderId = null;

        DB::transaction(function () use ($tableId, $deliveryCost, &$orderId) {
            if ($tableId) {
                $wasFree = Table::where('id', $tableId)->where('status', 'free')->exists();
                Table::where('id', $tableId)->update(['status' => 'occupied']);
                if ($wasFree) {
                    $this->dispatch('notify', message: 'Mesa ' . $this->orderingTableNumber . ' ocupada! Compartilhe o cardapio com o cliente.');
                }
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
                'payment_method' => $this->orderType === 'entrega' ? $this->orderPaymentMethod : null,
                'payment_change' => $this->orderPaymentMethod === 'cash' ? $this->cashAmount : null,
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

        $this->cancelOrdering();
        $this->dispatch('orderUpdated');
        $this->dispatch('notifyNewOrder');
        $this->dispatch('notify', message: "Pedido #{$orderId} criado com sucesso!");

        if ($this->orderType === 'entrega') {
            app(DeliveryNotificationService::class)->newOrderAvailable($order);
        }
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

    #[Computed]
    public function tables()
    {
        $tenant = Auth::user()->tenant;
        return $tenant->manageableTables()->with('tenant')->withCount(['orders' => function ($q) {
            $q->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega']);
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

    public function render()
    {
        return view('livewire.admin.table-grid');
    }
}
