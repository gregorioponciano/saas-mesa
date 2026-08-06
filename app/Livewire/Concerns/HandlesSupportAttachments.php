<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Upload de anexo nas mensagens de suporte (print de erro, comprovante,
 * foto de produto com problema). Mesmo padrão de validação (mimes, max)
 * e de armazenamento (Storage::disk('public')) usado em MenuManager.php,
 * Settings.php e EfiCredentialsManager.php.
 *
 * Os métodos de envio de mensagem chamam supportAttachmentRule() na
 * validação e storeSupportAttachment() no create() (mesclando o retorno
 * com os dados da SupportTicketMessage).
 */
trait HandlesSupportAttachments
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $attachment = null;

    public function supportAttachmentRule(): string
    {
        return 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
    }

    /**
     * Persiste o anexo (quando presente) e devolve as colunas da mensagem.
     *
     * @return array{attachment_path: ?string, attachment_original_name: ?string, attachment_mime: ?string}
     */
    public function storeSupportAttachment(): array
    {
        $attachment = [
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_mime' => null,
        ];

        if (! $this->attachment) {
            return $attachment;
        }

        $path = Storage::disk('public')->putFile('support-attachments', $this->attachment);

        return [
            'attachment_path' => $path,
            'attachment_original_name' => $this->attachment->getClientOriginalName(),
            'attachment_mime' => $this->attachment->getMimeType(),
        ];
    }

    public function resetSupportAttachment(): void
    {
        $this->attachment = null;
        $this->resetValidation('attachment');
    }

    /**
     * Guarda contra cliques duplos / envio repetido de mensagens: limita por
     * usuário + chave de ação (ex.: "reply:42"). Quando estoura, a chamada
     * deve abortar e avisar o usuário — o helper retorna false.
     */
    protected function supportActionAllowed(string $actionKey, int $maxAttempts = 10, int $decaySeconds = 60): bool
    {
        return RateLimiter::attempt(
            'support.'.Auth::id().'.'.$actionKey,
            $maxAttempts,
            fn () => true,
            $decaySeconds,
        );
    }

    protected function notifySupportDenied(string $message): void
    {
        $this->dispatch('notify', message: $message, type: 'error');
    }
}
