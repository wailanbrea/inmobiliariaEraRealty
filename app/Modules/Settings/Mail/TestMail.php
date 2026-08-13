<?php

namespace App\Modules\Settings\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de prueba de la pantalla de configuracion.
 *
 * No implementa ShouldQueue a proposito: la prueba tiene que ser sincrona
 * para poder decirle al administrador si funciono o no.
 */
class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('admin/settings.mail.test_subject', [
                'site' => setting('site_name', config('app.name')),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.test',
            with: [
                'siteName' => setting('site_name', config('app.name')),
                'sentAt' => now()->format('d/m/Y H:i:s'),
            ],
        );
    }
}
