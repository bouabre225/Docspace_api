<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AdminDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $data,
        public string $periode,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DocSpace Admin] Récap quotidien — ' . $this->periode,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: ['List-Unsubscribe' => '<https://docspace.bj/admin/stats>'],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-digest');
    }
}
