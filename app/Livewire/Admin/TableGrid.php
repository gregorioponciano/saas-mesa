<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TableGrid extends Component
{
    public string $filter = 'all';

    public ?int $selectedTableId = null;

    public ?int $selectedOrderId = null;

    public ?array $orderDetail = null;

    public bool $showPaymentModal = false;
    public ?int $paymentOrderId = null;
    public string $paymentMethod = 'pix';
    public float $paymentAmount = 0;
    public string $paymentNotes = '';

    protected $listeners = ['orderUpdated' => '$refresh', 'notifyNewOrder'];

    public function notifyNewOrder(): void
    {
        $this->dispatch('notify', message: 'Novo pedido recebido!');
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

        $activeOrders = Order::where('table_id', $this->selectedTableId)
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega'])
            ->with('items', 'payments')
            ->latest()
            ->get();

        if ($activeOrders->isNotEmpty()) {
            $this->selectedOrderId = $activeOrders->first()->id;
            $this->orderDetail = $activeOrders->map(fn($order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total' => $order->total,
                'status' => $order->status,
                'type' => $order->type,
                'statusLabel' => $order->statusLabel(),
                'statusColor' => $order->statusClasses(),
                'nextStatus' => $order->nextStatus(),
                'nextStatusLabel' => $order->statusFlowLabels()[$order->status] ?? 'Avançar',
                'has_payment' => $order->hasPayment(),
                'pending_payment' => $order->pendingPaymentAmount(),
                'items' => $order->items->map(fn($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]),
            ])->toArray();
        } else {
            $this->selectedOrderId = null;
            $this->orderDetail = null;
        }
    }

    public function advanceOrder(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $nextStatus = $order->nextStatus();

        if ($nextStatus) {
            $this->updateOrderStatus($orderId, $nextStatus);
        }
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);

        if ($order->table_id && $status === 'novo') {
            $order->table()->update(['status' => 'occupied']);
        }

        $this->loadOrderDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Status do pedido atualizado!');
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

        if ($order->pendingPaymentAmount() <= 0) {
            $order->update([
                'status' => 'fechado',
                'bill_closed_at' => now(),
            ]);

            if ($order->table_id) {
                $hasOther = \App\Models\Table::hasOtherActiveOrders($order->table_id, $order->id);
                if (!$hasOther) {
                    $order->table()->update(['status' => 'free']);
                }
            }
        }

        $this->showPaymentModal = false;
        $this->paymentOrderId = null;
        $this->paymentAmount = 0;
        $this->paymentNotes = '';
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

    public function freeTable(int $tableId): void
    {
        $table = Table::findOrFail($tableId);

        $activeOrders = Order::where('table_id', $tableId)
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega'])
            ->get();

        foreach ($activeOrders as $activeOrder) {
            $activeOrder->update(['status' => 'entregue']);
        }

        $table->update(['status' => 'free']);

        $this->closeDetail();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa ' . $table->number . ' liberada!');
    }

    public function closeDetail(): void
    {
        $this->selectedTableId = null;
        $this->selectedOrderId = null;
        $this->orderDetail = null;
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
