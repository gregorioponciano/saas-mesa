<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Propriedades computadas ([Computed]) reconhecidas pelo PHPStan.
 *
 * @property int $activeOrdersCount
 * @property int $usersCount
 * @property int $activeProductsCount
 * @property int $disabledProductsCount
 * @property int $tablesCount
 * @property int $occupiedTablesCount
 * @property int $openTicketsCount
 */
class SidebarCounts extends Component
{
    public ?int $lastNotifiedOrderId = null;

    public ?int $lastNotifiedTicketId = null;

    private function activeTicketStatuses(): array
    {
        return ['aberto', 'em_atendimento', 'aguardando_cliente'];
    }

    public function mount(): void
    {
        $this->lastNotifiedOrderId = Order::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->latest()->value('id');
        $this->lastNotifiedTicketId = SupportTicket::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', $this->activeTicketStatuses())
            ->latest()->value('id');
    }

    #[Computed]
    public function activeOrdersCount(): int
    {
        return Order::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])->count();
    }

    #[Computed]
    public function usersCount(): int
    {
        return User::where('tenant_id', Auth::user()->tenant_id)->count();
    }

    #[Computed]
    public function activeProductsCount(): int
    {
        return $this->productStats()['active'];
    }

    #[Computed]
    public function disabledProductsCount(): int
    {
        return $this->productStats()['total'] - $this->productStats()['active'];
    }

    #[Computed]
    public function tablesCount(): int
    {
        return $this->tableStats()['total'];
    }

    #[Computed]
    public function occupiedTablesCount(): int
    {
        return $this->tableStats()['occupied'];
    }

    #[Computed]
    public function openTicketsCount(): int
    {
        return SupportTicket::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', $this->activeTicketStatuses())->count();
    }

    private function tableStats(): array
    {
        $tenant = Auth::user()?->tenant;

        if (! $tenant) {
            return ['total' => 0, 'occupied' => 0];
        }

        $row = $tenant->manageableTables()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(status = "occupied"), 0) as occupied')
            ->first();

        return ['total' => (int) $row->total, 'occupied' => (int) $row->occupied];
    }

    private function productStats(): array
    {
        $row = Product::where('tenant_id', auth()->user()->tenant_id)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(status = "active"), 0) as active')
            ->first();

        return ['total' => (int) $row->total, 'active' => (int) $row->active];
    }

    public function checkNewOrders(): void
    {
        $latest = Order::where('tenant_id', auth()->user()->tenant_id)
            ->select('id', 'customer_name', 'type', 'table_id')
            ->with('table:id,number')
            ->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
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

    public function checkNewTickets(): void
    {
        $latest = SupportTicket::where('tenant_id', auth()->user()->tenant_id)
            ->select('id', 'subject', 'user_id')
            ->with('user:id,name')
            ->whereIn('status', $this->activeTicketStatuses())
            ->latest()
            ->first();

        if ($latest && $latest->id !== $this->lastNotifiedTicketId) {
            $this->lastNotifiedTicketId = $latest->id;
            $userName = $latest->user?->name ?? 'Cliente';
            $this->dispatch('notify', message: "Novo ticket de suporte: {$latest->subject} - {$userName}");
        }
    }

    public function render()
    {
        $this->checkNewOrders();
        $this->checkNewTickets();

        return view('livewire.admin.sidebar-counts', [
            'activeOrdersCount' => $this->activeOrdersCount,
            'usersCount' => $this->usersCount,
            'activeProductsCount' => $this->activeProductsCount,
            'disabledProductsCount' => $this->disabledProductsCount,
            'tablesCount' => $this->tablesCount,
            'occupiedTablesCount' => $this->occupiedTablesCount,
            'openTicketsCount' => $this->openTicketsCount,
            'isAdmin' => Auth::user()?->isAdmin() ?? false,
        ]);
    }
}
