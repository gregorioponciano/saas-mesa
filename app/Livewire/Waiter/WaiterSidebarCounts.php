<?php

namespace App\Livewire\Waiter;

use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WaiterSidebarCounts extends Component
{
    public ?int $lastNotifiedOrderId = null;

    public function mount(): void
    {
        $this->lastNotifiedOrderId = Order::whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->latest()->value('id');
    }

    #[Computed]
    public function pendingOrdersCount(): int
    {
        return Order::where('status', 'novo')->count();
    }

    #[Computed]
    public function occupiedTablesCount(): int
    {
        $tenant = Auth::user()->tenant;
        $q = $tenant->manageableTables();
        return (clone $q)->where('status', 'occupied')->count();
    }

    public function checkNewOrders(): void
    {
        $latest = Order::with('table')->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->latest()
            ->first();

        if ($latest && $latest->id !== $this->lastNotifiedOrderId) {
            $this->lastNotifiedOrderId = $latest->id;
            $customerName = $latest->customer_name ?? 'Cliente';
            $tableInfo = $latest->table ? " na Mesa {$latest->table->number}" : '';
            $typeLabel = $latest->type === 'entrega' ? ' (Entrega)' : ($latest->type === 'retirada' ? ' (Retirada)' : '');
            $this->dispatch('notify', message: "Pedido #{$latest->id} de {$customerName}{$tableInfo}{$typeLabel} recebido!");
        }
    }

    public function render()
    {
        $this->checkNewOrders();

        return view('livewire.waiter.waiter-sidebar-counts', [
            'pendingOrdersCount' => $this->pendingOrdersCount,
            'occupiedTablesCount' => $this->occupiedTablesCount,
        ]);
    }
}
