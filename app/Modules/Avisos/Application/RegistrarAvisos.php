<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application;

use App\Modules\Avisos\Application\Preferencias\PreferenciasDeMail;
use App\Modules\Avisos\Domain\Destinatario;
use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Avisos\Domain\RedactorDeAvisos;
use App\Modules\Avisos\Infrastructure\Mail\AvisoPedidoMail;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Crea un aviso por destinatario con la foto del momento y su `dedupe_key` único: un reintento o una
 * segunda corrida chocan con la clave y no duplican. El mail se encola (cola `mail`) **solo si el aviso se
 * acaba de crear** y a esa persona le corresponde por mail (RN-AVI-06, RN-AVI-19).
 */
final class RegistrarAvisos
{
    public function __construct(
        private readonly RedactorDeAvisos $redactor,
        private readonly PreferenciasDeMail $preferencias,
    ) {}

    /**
     * @param  list<Destinatario>  $destinatarios
     * @return int cuántos avisos se crearon ahora
     */
    public function handle(EventoAvisado $evento, array $destinatarios): int
    {
        if ($destinatarios === []) {
            return 0;
        }

        $mails = User::query()->whereIn('id', array_map(static fn (Destinatario $d): int => $d->userId, $destinatarios))->pluck('email', 'id');
        $creados = 0;

        foreach ($destinatarios as $destinatario) {
            $aviso = Aviso::query()->createOrFirst(['dedupe_key' => $evento->clave($destinatario)], [
                'user_id' => $destinatario->userId,
                'tipo' => $destinatario->tipo,
                'pedido_id' => $evento->pedidoId,
                'actor_id' => $evento->actorId,
                'datos' => $this->datos($evento, $destinatario),
            ]);

            if (! $aviso->wasRecentlyCreated) {
                continue;
            }

            $creados++;
            $mail = $mails[$destinatario->userId] ?? null;
            if (is_string($mail) && $this->preferencias->corresponde($destinatario->userId, $destinatario->tipo, $destinatario->papel)) {
                Mail::to($mail)->queue(new AvisoPedidoMail($aviso->id));
            }
        }

        return $creados;
    }

    /**
     * La foto del momento (avisos.md §4.1): el texto ya redactado y lo que el mail necesita.
     *
     * @return array<string, mixed>
     */
    private function datos(EventoAvisado $evento, Destinatario $destinatario): array
    {
        $c = $evento->contexto;

        return [
            'texto' => $this->redactor->texto($destinatario->tipo, $destinatario->papel, $c),
            'asunto' => $this->redactor->asunto($destinatario->tipo, $destinatario->papel, $c),
            'papel' => $destinatario->papel->value,
            'tarea' => $c->tarea,
            'obra' => $c->obraNombre !== null ? ['id' => $evento->obraId, 'nombre_trabajo' => $c->obraNombre] : null,
            'actor_nombre' => $c->actorNombre,
            'solicitante_nombre' => $c->solicitanteNombre,
            'sector_nombre' => $c->sectorNombre,
            'extracto' => RedactorDeAvisos::extracto($c->extracto),
            'comprobantes' => $c->comprobantes,
            'fecha_entrega' => $evento->fechaEntrega,
            'prioridad' => $evento->prioridad,
        ];
    }
}
