<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Infrastructure\Mail;

use App\Modules\Avisos\Application\Preferencias\PreferenciasDeMail;
use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Avisos\Domain\Papel;
use App\Modules\Avisos\Domain\RedactorDeAvisos;
use App\Shared\Mail\QueuedMailable;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\SentMessage;

/**
 * Mail de un aviso (RN-AVI-20, RN-AVI-21): por la cola `mail`, desde noreply, uno por persona. Viaja solo el
 * id del aviso; al enviarse vuelve a mirar que la persona siga activa y con el mail activado para ese tipo,
 * y si no, no sale. Sin importes ni adjuntos.
 */
final class AvisoPedidoMail extends QueuedMailable
{
    private const PRIORIDADES = ['urgente' => 'Urgente', 'prioritario' => 'Prioritario', 'normal' => 'Normal'];

    private ?Aviso $aviso = null;

    public function __construct(public readonly int $avisoId)
    {
        parent::__construct();
    }

    /**
     * @param  Factory|Mailer  $mailer
     */
    public function send($mailer): ?SentMessage
    {
        if (! $this->corresponde()) {
            return null;
        }

        return parent::send($mailer);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->aviso()->dato('asunto') ?? $this->aviso()->dato('texto') ?? '');
    }

    public function content(): Content
    {
        $aviso = $this->aviso();
        $fecha = $aviso->dato('fecha_entrega');
        $obra = $aviso->datos['obra'] ?? null;

        return new Content(view: 'avisos.mail.aviso', with: [
            'texto' => $aviso->dato('texto'),
            'tarea' => $aviso->dato('tarea'),
            'obra' => is_array($obra) && is_string($obra['nombre_trabajo'] ?? null) ? $obra['nombre_trabajo'] : RedactorDeAvisos::SIN_OBRA,
            'entrega' => $fecha !== null ? implode('/', array_reverse(explode('-', $fecha))) : null,
            'prioridad' => self::PRIORIDADES[$aviso->dato('prioridad') ?? ''] ?? null,
            'solicitante' => $aviso->dato('solicitante_nombre'),
            'link' => config()->string('identidad.frontend_url').'/pedidos/'.$aviso->pedido_id,
        ]);
    }

    private function corresponde(): bool
    {
        $aviso = $this->aviso();
        $papel = Papel::tryFrom($aviso->dato('papel') ?? '') ?? Papel::Propietario;

        return $aviso->user->activo
            && app(PreferenciasDeMail::class)->corresponde($aviso->user_id, $aviso->tipo, $papel);
    }

    private function aviso(): Aviso
    {
        return $this->aviso ??= Aviso::query()->with('user')->findOrFail($this->avisoId);
    }
}
