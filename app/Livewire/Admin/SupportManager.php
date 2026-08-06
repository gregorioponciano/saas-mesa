<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesSupportAttachments;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SupportManager extends Component
{
    use HandlesSupportAttachments;

    public string $statusFilter = 'all';

    public string $categoryFilter = 'all';

    public string $priorityFilter = 'all';

    public string $search = '';

    public string $tab = 'tickets';

    public ?int $viewingTicketId = null;

    public ?array $viewingTicket = null;

    public bool $showDetail = false;

    public string $replyBody = '';

    public bool $replyIsInternal = false;

    public ?int $reassignToUserId = null;

    #[Computed]
    public function tickets()
    {
        return SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', auth()->user()->tenant_id)->with(['user', 'lastMessage', 'assignedTo'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->search, fn ($q) => $q->where('subject', 'like', "%{$this->search}%"))
            ->latest('updated_at')
            ->take(100)
            ->get();
    }

    #[Computed]
    public function metrics(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total' => SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', $tenantId)->count(),
            'abertos' => SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', $tenantId)->where('status', 'aberto')->count(),
            'em_atendimento' => SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', $tenantId)->where('status', 'em_atendimento')->count(),
            'aguardando_cliente' => SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', $tenantId)->where('status', 'aguardando_cliente')->count(),
            'resolvidos_hoje' => SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', $tenantId)->where('status', 'resolvido')->whereDate('updated_at', today())->count(),
            'tempo_medio_dias' => round(SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', $tenantId)->whereIn('status', ['resolvido', 'fechado'])
                ->avg(DB::raw('TIMESTAMPDIFF(HOUR, created_at, updated_at)')) / 24, 1),
            'por_categoria' => SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', $tenantId)->selectRaw('category, count(*) as total')
                ->groupBy('category')->pluck('total', 'category'),
        ];
    }

    #[Computed]
    public function staffUsers()
    {
        return User::where('tenant_id', Auth::user()->tenant_id)
            ->where(function ($q) {
                $q->where('role', 'admin')->orWhere('role', 'atendente');
            })->get();
    }

    public function viewTicket(int $ticketId): void
    {
        $ticket = SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', auth()->user()->tenant_id)->with(['messages' => fn ($q) => $q->oldest(), 'user', 'assignedTo'])
            ->findOrFail($ticketId);

        $this->viewingTicketId = $ticketId;
        $this->viewingTicket = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'categoryLabel' => $ticket->categoryLabel(),
            'priority' => $ticket->priority,
            'priorityLabel' => $ticket->priorityLabel(),
            'priorityClasses' => $ticket->priorityClasses(),
            'status' => $ticket->status,
            'statusLabel' => $ticket->statusLabel(),
            'statusClasses' => $ticket->statusClasses(),
            'created_at' => $ticket->created_at->format('d/m/Y H:i'),
            'updated_at' => $ticket->updated_at->format('d/m/Y H:i'),
            'user_name' => $ticket->user?->name ?? '—',
            'assigned_to' => $ticket->assignedTo?->name,
            'assigned_to_id' => $ticket->assigned_to,
            'order_id' => $ticket->order_id,
            'messages' => $ticket->messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'author_role' => $m->author_role,
                'author_name' => $m->author_name,
                'is_internal' => $m->is_internal,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'attachment_path' => $m->attachment_path,
                'attachment_name' => $m->attachment_original_name,
                'attachment_mime' => $m->attachment_mime,
                'attachment_url' => $m->attachmentUrl(),
            ])->toArray(),
        ];
        $this->showDetail = true;
        $this->replyBody = '';
        $this->replyIsInternal = false;
        $this->reassignToUserId = null;
        $this->resetSupportAttachment();
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyBody' => 'required|string|min:1|max:2000',
            'attachment' => $this->supportAttachmentRule(),
        ]);

        $ticket = SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->viewingTicketId);

        if ($ticket->status === 'fechado') {
            $this->notifySupportDenied('Este chamado está encerrado. Reabra-o para responder.');

            return;
        }

        if (! $this->supportActionAllowed('reply:'.$ticket->id, 20)) {
            $this->notifySupportDenied('Você está respondendo rápido demais. Aguarde alguns segundos.');

            return;
        }

        $user = Auth::user();

        SupportTicketMessage::create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $this->replyBody,
            'is_internal' => $this->replyIsInternal,
            'author_role' => 'admin',
            'author_name' => $user->name,
        ], $this->storeSupportAttachment()));

        if (! $this->replyIsInternal) {
            if ($ticket->status === 'aberto') {
                $ticket->update(['status' => 'em_atendimento']);
            }
            $ticket->update(['status' => 'aguardando_cliente']);
        }

        $this->replyBody = '';
        $this->resetSupportAttachment();
        $this->viewTicket($this->viewingTicketId);
        $this->dispatch('notify', message: 'Resposta enviada!');
    }

    public function updateStatus(int $ticketId, string $status): void
    {
        SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', auth()->user()->tenant_id)->findOrFail($ticketId)->update(['status' => $status]);
        if ($this->viewingTicketId === $ticketId) {
            $this->viewTicket($ticketId);
        }
        $this->dispatch('notify', message: 'Status atualizado!');
    }

    public function reassignTicket(int $ticketId): void
    {
        $this->validate(['reassignToUserId' => 'required|exists:users,id']);
        SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', auth()->user()->tenant_id)->findOrFail($ticketId)->update(['assigned_to' => $this->reassignToUserId]);
        $this->viewTicket($ticketId);
        $this->dispatch('notify', message: 'Ticket reatribuído!');
    }

    public function forceClose(int $ticketId): void
    {
        SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', auth()->user()->tenant_id)->findOrFail($ticketId)->update(['status' => 'fechado']);
        if ($this->viewingTicketId === $ticketId) {
            $this->viewTicket($ticketId);
        }
        $this->dispatch('notify', message: 'Ticket fechado forçadamente.');
    }

    public function deleteTicket(int $ticketId): void
    {
        SupportTicket::where('audience', SupportTicket::AUDIENCE_TENANT)->where('tenant_id', auth()->user()->tenant_id)->findOrFail($ticketId)->delete();
        $this->showDetail = false;
        $this->viewingTicketId = null;
        $this->viewingTicket = null;
        $this->dispatch('notify', message: 'Ticket deletado!');
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->viewingTicketId = null;
        $this->viewingTicket = null;
    }

    public function render()
    {
        return view('livewire.admin.support-manager')->extends('layouts.admin');
    }
}
