<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Identidad\Domain\Enums\Prioridad;

/** Lo que vino en Editar (W-32): solo los campos presentes. */
final readonly class CambiosPedido
{
    /**
     * @param  array<string, string|null>  $textos  tarea, con_quien, aclaraciones y fechas ("AAAA-MM-DD")
     * @param  list<int>|null  $adjuntos  la lista completa; null = no se tocan
     */
    public function __construct(
        public array $textos,
        public bool $cambiaObra,
        public ?int $obraId,
        public ?array $adjuntos,
        public ?Prioridad $prioridad,
        public bool $usarCalculada,
    ) {}

    public function tocaPrioridad(): bool
    {
        return $this->prioridad !== null || $this->usarCalculada;
    }
}
