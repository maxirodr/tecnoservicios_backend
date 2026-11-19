<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application;

use App\Modules\Avisos\Domain\ContextoDelAviso;
use App\Modules\Avisos\Domain\Destinatario;

/**
 * Lo que pasó y la foto del momento, común a todos los destinatarios de un evento. Arma el `dedupe_key`
 * de cada uno (avisos.md §6): `{tipo}:{actividad}:{persona}` para los eventos de Pedidos y
 * `recordatorio:{pedido}:{fecha_entrega}:{persona}` para "vence mañana".
 */
final readonly class EventoAvisado
{
    private function __construct(
        public int $pedidoId,
        public ?int $actorId,
        private ?int $actividadId,
        public ContextoDelAviso $contexto,
        public ?int $obraId,
        public ?string $fechaEntrega,
        public ?string $prioridad,
    ) {}

    public static function deActividad(int $actividadId, int $pedidoId, int $actorId, ContextoDelAviso $contexto, ?int $obraId, ?string $fechaEntrega, string $prioridad): self
    {
        return new self($pedidoId, $actorId, $actividadId, $contexto, $obraId, $fechaEntrega, $prioridad);
    }

    public static function recordatorio(int $pedidoId, string $fechaEntrega, ContextoDelAviso $contexto): self
    {
        return new self($pedidoId, null, null, $contexto, null, $fechaEntrega, null);
    }

    public function clave(Destinatario $destinatario): string
    {
        return $this->actividadId !== null
            ? "{$destinatario->tipo->value}:{$this->actividadId}:{$destinatario->userId}"
            : "recordatorio:{$this->pedidoId}:{$this->fechaEntrega}:{$destinatario->userId}";
    }
}
