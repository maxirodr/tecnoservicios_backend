<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Domain\Models\Pedido;

/** Lo que muestra W-09 de un pedido para una persona: el pedido con todo cargado, si lo sigue y sus botones. */
final readonly class DetallePedido
{
    /**
     * @param  array<string, bool>  $puede
     * @param  list<array{id: int, nombre: string}>|null  $avisados  solo al crear y al reasignar
     */
    public function __construct(
        public Pedido $pedido,
        public bool $sigo,
        public array $puede,
        public ?int $diasAnticipacion,
        public ?Prioridad $calculada,
        public ?array $avisados = null,
    ) {}
}
