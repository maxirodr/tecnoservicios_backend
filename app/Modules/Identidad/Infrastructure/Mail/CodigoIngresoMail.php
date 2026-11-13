<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure\Mail;

use App\Shared\Mail\QueuedMailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mail de cuenta con el código para entrar (ADR-015, RN-IDE-02/08). Por la cola `mail`, después
 * del commit del código.
 */
final class CodigoIngresoMail extends QueuedMailable
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $codigo,
        public readonly int $minutos,
    ) {
        parent::__construct();
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu código para entrar a Tecnoservicios');
    }

    public function content(): Content
    {
        return new Content(view: 'identidad.mail.codigo');
    }
}
