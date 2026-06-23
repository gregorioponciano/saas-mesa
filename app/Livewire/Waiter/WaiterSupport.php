<?php

namespace App\Livewire\Waiter;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WaiterSupport extends Component
{
    public $tenant;

    public string $statusFilter = 'aberto';
    public string $categoryFilter = 'all';
    public string $search = '';

    public ?int $viewingTicketId = null;
    public ?array $viewingTicket = null;
    public bool $showDetail = false;

    public string $replyBody = '';
    public bool $replyIsInternal = false;

    public function mount(): void
    {
        $this->tenant = Auth::user()->tenant;
    }

    #[Computed]
    public function tickets()
    {
        return SupportTicket::with(['user', 'lastMessage', 'assignedTo'])
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->search, fn($q) => $q->where('subject', 'like', "%{$this->search}%"))
            ->latest('updated_at')
            ->take(100)
            ->get();
    }

    public function viewTicket(int $ticketId): void
    {
        $ticket = SupportTicket::with(['messages' => fn($q) => $q->oldest(), 'user', 'assignedTo'])
            ->findOrFail($ticketId);

        $this->viewingTicketId = $ticketId;
        $this->viewingTicket = [
            'id'          => $ticket->id,
            'subject'     => $ticket->subject,
            'category'    => $ticket->category,
            'categoryLabel' => $ticket->categoryLabel(),
            'priority'    => $ticket->priority,
            'priorityLabel' => $ticket->priorityLabel(),
            'priorityClasses' => $ticket->priorityClasses(),
            'status'      => $ticket->status,
            'statusLabel' => $ticket->statusLabel(),
            'statusClasses' => $ticket->statusClasses(),
            'created_at'  => $ticket->created_at->format('d/m/Y H:i'),
            'updated_at'  => $ticket->updated_at->format('d/m/Y H:i'),
            'user_name'   => $ticket->user?->name ?? '—',
            'assigned_to' => $ticket->assignedTo?->name,
            'assigned_to_id' => $ticket->assigned_to,
            'order_id'    => $ticket->order_id,
            'messages'    => $ticket->messages->map(fn($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'author_role' => $m->author_role,
                'author_name' => $m->author_name,
                'is_internal' => $m->is_internal,
                'created_at'  => $m->created_at->format('d/m/Y H:i'),
            ])->toArray(),
        ];
        $this->showDetail = true;
        $this->replyBody = '';
        $this->replyIsInternal = false;
    }

    public function sendReply(): void
    {
        $this->validate(['replyBody' => 'required|string|min:1|max:2000']);

        $ticket = SupportTicket::findOrFail($this->viewingTicketId);
        $user = Auth::user();
        $authorRole = $user->isAdmin() ? 'admin' : 'atendente';

        SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $user->id,
            'body'        => $this->replyBody,
            'is_internal' => $this->replyIsInternal,
            'author_role' => $authorRole,
            'author_name' => $user->name,
        ]);

        if (!$this->replyIsInternal) {
            if ($ticket->status === 'aberto') {
                $ticket->update(['status' => 'em_atendimento']);
            }
            $ticket->update(['status' => 'aguardando_cliente']);
        }

        $this->replyBody = '';
        $this->viewTicket($this->viewingTicketId);
        $this->dispatch('notify', message: 'Resposta enviada!');
    }

    public function updateStatus(int $ticketId, string $status): void
    {
        SupportTicket::findOrFail($ticketId)->update(['status' => $status]);

        if ($this->viewingTicketId === $ticketId) {
            $this->viewTicket($ticketId);
        }
        $this->dispatch('notify', message: 'Status atualizado!');
    }

    public function assignToMe(int $ticketId): void
    {
        SupportTicket::findOrFail($ticketId)->update(['assigned_to' => Auth::id()]);

        if ($this->viewingTicketId === $ticketId) {
            $this->viewTicket($ticketId);
        }
        $this->dispatch('notify', message: 'Ticket assumido!');
    }

    public function unassign(int $ticketId): void
    {
        SupportTicket::findOrFail($ticketId)->update(['assigned_to' => null]);

        if ($this->viewingTicketId === $ticketId) {
            $this->viewTicket($ticketId);
        }
        $this->dispatch('notify', message: 'Ticket liberado.');
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->viewingTicketId = null;
        $this->viewingTicket = null;
    }

    public function render()
    {
        return view('livewire.waiter.waiter-support')->extends('layouts.waiter');
    }
}
