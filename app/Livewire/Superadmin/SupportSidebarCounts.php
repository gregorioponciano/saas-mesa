<?php

declare(strict_types=1);

namespace App\Livewire\Superadmin;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Contador de chamados abertos Empresa->Plataforma para o sidebar do
 * superadmin. Reaproveita a mesma regra de status ativos do SidebarCounts
 * do painel admin, mas filtrada por audience = 'platform'.
 *
 * O root faz wire:poll.5s e, quando surge um chamado novo, dispara a
 * notificação do topo da tela (mesmo padrão do SidebarCounts admin).
 *
 * @property int $openPlatformTicketsCount
 */
class SupportSidebarCounts extends Component
{
    public ?int $lastNotifiedPlatformTicketId = null;

    public function mount(): void
    {
        $this->lastNotifiedPlatformTicketId = SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->whereIn('status', SupportTicket::STATUS_OPEN)
            ->latest()->value('id');
    }

    #[Computed]
    public function openPlatformTicketsCount(): int
    {
        if (! Auth::user()?->isSuperAdmin()) {
            return 0;
        }

        return SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->whereIn('status', SupportTicket::STATUS_OPEN)
            ->count();
    }

    public function checkNewPlatformTickets(): void
    {
        $latest = SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->select('id', 'subject', 'tenant_id')
            ->with('tenant:id,name')
            ->whereIn('status', SupportTicket::STATUS_OPEN)
            ->latest()
            ->first();

        if ($latest && $latest->id !== $this->lastNotifiedPlatformTicketId) {
            $this->lastNotifiedPlatformTicketId = $latest->id;
            $tenantName = $latest->tenant?->name ?? 'Empresa';
            $this->dispatch('notify', message: "Novo chamado da {$tenantName}: {$latest->subject}");
        }
    }

    public function render()
    {
        if (! Auth::user()?->isSuperAdmin()) {
            return view('livewire.superadmin.support-sidebar-counts', ['openPlatformTicketsCount' => 0]);
        }

        $this->checkNewPlatformTickets();

        return view('livewire.superadmin.support-sidebar-counts', [
            'openPlatformTicketsCount' => $this->openPlatformTicketsCount,
        ]);
    }
}
