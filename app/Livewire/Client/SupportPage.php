<?php

namespace App\Livewire\Client;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SupportPage extends Component
{
    public $tenant;

    public string $tab = 'meus_tickets';

    public string $newSubject = '';
    public string $newCategory = 'outro';
    public string $newPriority = 'media';
    public string $newBody = '';
    public ?string $newOrderRef = null;

    public ?int $viewingTicketId = null;
    public ?array $viewingTicket = null;
    public bool $showTicketDetail = false;

    public string $replyBody = '';

    public function mount(): void
    {
        $this->tenant = Auth::user()->tenant;
    }

    #[Computed]
    public function myTickets()
    {
        return SupportTicket::with('lastMessage')
            ->where('user_id', Auth::id())
            ->latest('updated_at')
            ->get();
    }

    public function openTicket(): void
    {
        $this->validate([
            'newSubject'  => 'required|string|max:200',
            'newCategory' => 'required|in:pedido,pagamento,cardapio,entrega,conta,outro',
            'newPriority' => 'required|in:baixa,media,alta',
            'newBody'     => 'required|string|min:10|max:2000',
            'newOrderRef' => 'nullable|string|max:50',
        ]);

        $ticket = SupportTicket::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => Auth::id(),
            'subject'    => $this->newSubject,
            'category'   => $this->newCategory,
            'priority'   => $this->newPriority,
            'status'     => 'aberto',
            'order_id'   => $this->newOrderRef ?: null,
        ]);

        SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => Auth::id(),
            'body'        => $this->newBody,
            'author_role' => 'cliente',
            'author_name' => Auth::user()->name,
        ]);

        $this->reset(['newSubject', 'newCategory', 'newPriority', 'newBody', 'newOrderRef']);
        $this->tab = 'meus_tickets';
        $this->dispatch('notify', message: 'Ticket aberto com sucesso! Em breve nossa equipe entrará em contato.');
    }

    public function viewTicket(int $ticketId): void
    {
        $ticket = SupportTicket::with([
            'messages' => fn($q) => $q->where('is_internal', false)->oldest(),
            'assignedTo',
        ])->where('user_id', Auth::id())->findOrFail($ticketId);

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
            'assigned_to' => $ticket->assignedTo?->name,
            'order_id'    => $ticket->order_id,
            'messages'    => $ticket->messages->map(fn($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'author_role' => $m->author_role,
                'author_name' => $m->author_name,
                'created_at'  => $m->created_at->format('d/m/Y H:i'),
            ])->toArray(),
        ];
        $this->showTicketDetail = true;
        $this->replyBody = '';
    }

    public function sendReply(): void
    {
        $this->validate(['replyBody' => 'required|string|min:1|max:2000']);

        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($this->viewingTicketId);

        SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => Auth::id(),
            'body'        => $this->replyBody,
            'author_role' => 'cliente',
            'author_name' => Auth::user()->name,
        ]);

        if ($ticket->status === 'aguardando_cliente') {
            $ticket->update(['status' => 'em_atendimento']);
        }

        $this->replyBody = '';
        $this->viewTicket($this->viewingTicketId);
        $this->dispatch('notify', message: 'Resposta enviada!');
    }

    public function closeTicket(int $ticketId): void
    {
        SupportTicket::where('user_id', Auth::id())->findOrFail($ticketId)->update(['status' => 'fechado']);
        $this->showTicketDetail = false;
        $this->dispatch('notify', message: 'Ticket fechado.');
    }

    public function backToList(): void
    {
        $this->showTicketDetail = false;
        $this->viewingTicketId = null;
        $this->viewingTicket = null;
    }

    public function render()
    {
        return view('livewire.client.support-page')->extends('layouts.client');
    }
}
