<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $token,
        public string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Redefinição de Senha - ' . $this->tenant->name,
        );
    }

    public function content(): Content
    {
        $url = route('waiter.reset.form', [
            'slug' => $this->tenant->slug,
            'token' => $this->token,
        ]);

        return new Content(
            html: 'emails.reset-password',
            with: [
                'url' => $url,
                'tenantName' => $this->tenant->name,
                'email' => $this->email,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
