<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure\Mail;

use App\Shared\Mail\QueuedMailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * "Olvidé mi contraseña" (identidad.md §8, RN-IDE-06/07): link de 60 minutos, sirve una vez.
 */
final class RecuperacionMail extends QueuedMailable
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $url,
    ) {
        parent::__construct();
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Elegí una contraseña nueva');
    }

    public function content(): Content
    {
        return new Content(view: 'identidad.mail.recuperacion');
    }
}
