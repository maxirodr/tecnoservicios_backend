<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure\Mail;

use App\Shared\Mail\QueuedMailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mail de cuenta al dar de alta un usuario (identidad.md §8, RN-IDE-09/10): link de 7 días para
 * elegir su contraseña. Sale después del commit del alta.
 */
final class BienvenidaMail extends QueuedMailable
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $email,
        public readonly ?string $altaPor,
        public readonly string $url,
    ) {
        parent::__construct();
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu usuario en el sistema de Tecnoservicios');
    }

    public function content(): Content
    {
        return new Content(view: 'identidad.mail.bienvenida');
    }
}
