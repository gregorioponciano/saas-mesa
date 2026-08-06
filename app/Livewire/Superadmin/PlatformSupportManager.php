<?php

declare(strict_types=1);

namespace App\Livewire\Superadmin;

use App\Livewire\Concerns\HandlesSupportAttachments;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Canal Empresa -> Plataforma visto pelo superadmin.
 *
 * O TenantScope::resolveTenantId() retorna null para superadmin, então as
 * queries abaixo enxergam tickets de todos os tenants automaticamente —
 * só filtramos por audience = 'platform'.
 */
class PlatformSupportManager extends Component
{
    use HandlesSupportAttachments;

    public string $statusFilter = 'all';

    public string $search = '';

    public ?int $viewingTicketId = null;

    public ?array $viewingTicket = null;

    public bool $showDetail = false;

    public string $replyBody = '';

    #[Computed]
    public function tickets()
    {
        return SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->with(['tenant:id,name', 'user', 'lastMessage'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('subject', 'like', "%{$this->search}%")
                        ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('updated_at')
            ->take(100)
            ->get();
    }

    public function viewTicket(int $ticketId): void
    {
        $ticket = SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->with(['messages' => fn ($q) => $q->oldest(), 'tenant:id,name', 'user'])
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
            'tenant_name' => $ticket->tenant?->name ?? '—',
            'user_name' => $ticket->user?->name ?? '—',
            'order_id' => $ticket->order_id,
            'messages' => $ticket->messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'author_role' => $m->author_role,
                'author_name' => $m->author_name,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'attachment_path' => $m->attachment_path,
                'attachment_name' => $m->attachment_original_name,
                'attachment_mime' => $m->attachment_mime,
                'attachment_url' => $m->attachmentUrl(),
            ])->toArray(),
        ];
        $this->showDetail = true;
        $this->replyBody = '';
        $this->resetSupportAttachment();
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyBody' => 'required|string|min:1|max:2000',
            'attachment' => $this->supportAttachmentRule(),
        ]);

        $ticket = SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)->findOrFail($this->viewingTicketId);

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
            'is_internal' => false,
            'author_role' => 'platform',
            'author_name' => $user->name,
        ], $this->storeSupportAttachment()));

        if ($ticket->status === 'aberto' || $ticket->status === 'aguardando_cliente' || $ticket->status === 'resolvido') {
            $ticket->update(['status' => 'em_atendimento']);
        }

        $this->replyBody = '';
        $this->resetSupportAttachment();
        $this->viewTicket($this->viewingTicketId);
        $this->dispatch('notify', message: 'Resposta enviada à empresa!');
    }

    public function updateStatus(int $ticketId, string $status): void
    {
        SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->findOrFail($ticketId)->update(['status' => $status]);
        if ($this->viewingTicketId === $ticketId) {
            $this->viewTicket($ticketId);
        }
        $this->dispatch('notify', message: 'Status atualizado!');
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->viewingTicketId = null;
        $this->viewingTicket = null;
    }

    public function render()
    {
        return view('livewire.superadmin.platform-support-manager');
    }
}
