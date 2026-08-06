<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesSupportAttachments;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Canal Empresa (tenant) -> Plataforma (superadmin).
 *
 * Componente separado de SupportManager de propósito: tickets "plataforma"
 * têm semântica diferente dos tickets de cliente final (cliente->empresa) —
 * sem nota interna (vazaria para a plataforma), sem reatribuição para a
 * equipe local, sem zona de perigo e sem métricas de atendimento. Misturar
 * as duas audiências no mesmo SupportManager geraria tela confusa e queries
 * condicionais por tab. A UI reaproveita o visual de support-manager.blade.php.
 */
class PlatformSupport extends Component
{
    use HandlesSupportAttachments;

    public string $statusFilter = 'all';

    public string $search = '';

    public ?int $viewingTicketId = null;

    public ?array $viewingTicket = null;

    public bool $showDetail = false;

    public string $replyBody = '';

    public bool $showCreateForm = false;

    public string $newSubject = '';

    public string $newCategory = 'conta';

    public string $newPriority = 'media';

    public string $newBody = '';

    public ?string $newOrderRef = null;

    #[Computed]
    public function tickets()
    {
        return SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->with(['user', 'lastMessage'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where('subject', 'like', "%{$this->search}%"))
            ->latest('updated_at')
            ->take(100)
            ->get();
    }

    public function openTicket(): void
    {
        if (! $this->supportActionAllowed('open', 10)) {
            $this->notifySupportDenied('Muitos chamados em pouco tempo. Aguarde um minuto e tente de novo.');

            return;
        }

        $this->validate([
            'newSubject' => 'required|string|max:200',
            'newCategory' => 'required|in:pedido,pagamento,cardapio,entrega,conta,outro',
            'newPriority' => 'required|in:baixa,media,alta',
            'newBody' => 'required|string|min:10|max:2000',
            'newOrderRef' => 'nullable|string|max:50',
            'attachment' => $this->supportAttachmentRule(),
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'subject' => $this->newSubject,
            'category' => $this->newCategory,
            'priority' => $this->newPriority,
            'status' => 'aberto',
            'order_id' => $this->newOrderRef ?: null,
            'audience' => SupportTicket::AUDIENCE_PLATFORM,
        ]);

        SupportTicketMessage::create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'body' => $this->newBody,
            'author_role' => 'admin',
            'author_name' => Auth::user()->name,
        ], $this->storeSupportAttachment()));

        $this->reset(['newSubject', 'newCategory', 'newPriority', 'newBody', 'newOrderRef']);
        $this->resetSupportAttachment();
        $this->showCreateForm = false;
        $this->viewTicket($ticket->id);
        $this->dispatch('notify', message: 'Chamado aberto! Nossa equipe de plataforma vai analisar.');
    }

    public function viewTicket(int $ticketId): void
    {
        $ticket = SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)
            ->with(['messages' => fn ($q) => $q->oldest(), 'user'])
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

        if ($ticket->isClosed()) {
            $this->notifySupportDenied('Este chamado está encerrado. Reabra-o para enviar nova mensagem.');

            return;
        }

        if (! $this->supportActionAllowed('reply:'.$ticket->id, 20)) {
            $this->notifySupportDenied('Você está enviando mensagens rápido demais. Aguarde alguns segundos.');

            return;
        }

        $user = Auth::user();

        SupportTicketMessage::create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $this->replyBody,
            'is_internal' => false,
            'author_role' => 'admin',
            'author_name' => $user->name,
        ], $this->storeSupportAttachment()));

        if ($ticket->status === 'aberto') {
            $ticket->update(['status' => 'em_atendimento']);
        } elseif ($ticket->status === 'aguardando_cliente') {
            $ticket->update(['status' => 'em_atendimento']);
        }

        $this->replyBody = '';
        $this->resetSupportAttachment();
        $this->viewTicket($this->viewingTicketId);
        $this->dispatch('notify', message: 'Mensagem enviada à plataforma!');
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
        return view('livewire.admin.platform-support')->extends('layouts.admin');
    }
}
