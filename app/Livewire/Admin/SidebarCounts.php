<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SidebarCounts extends Component
{
    public ?int $lastNotifiedOrderId = null;

    public function mount(): void
    {
        $this->lastNotifiedOrderId = Order::whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->latest()->value('id');
    }

    #[Computed]
    public function activeOrdersCount(): int
    {
        return Order::whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])->count();
    }

    #[Computed]
    public function usersCount(): int
    {
        return User::where('tenant_id', Auth::user()->tenant_id)->count();
    }

    #[Computed]
    public function activeProductsCount(): int
    {
        return Product::active()->count();
    }

    #[Computed]
    public function disabledProductsCount(): int
    {
        return Product::where('status', '!=', 'active')->count();
    }

    #[Computed]
    public function tablesCount(): int
    {
        $tenant = Auth::user()->tenant;
        return $tenant->manageableTables()->count();
    }

    #[Computed]
    public function occupiedTablesCount(): int
    {
        $tenant = Auth::user()->tenant;
        return (clone $tenant->manageableTables())->where('status', 'occupied')->count();
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

        return view('livewire.admin.sidebar-counts', [
            'activeOrdersCount' => $this->activeOrdersCount,
            'usersCount' => $this->usersCount,
            'activeProductsCount' => $this->activeProductsCount,
            'disabledProductsCount' => $this->disabledProductsCount,
            'tablesCount' => $this->tablesCount,
            'occupiedTablesCount' => $this->occupiedTablesCount,
            'isAdmin' => Auth::user()?->isAdmin() ?? false,
        ]);
    }
}
