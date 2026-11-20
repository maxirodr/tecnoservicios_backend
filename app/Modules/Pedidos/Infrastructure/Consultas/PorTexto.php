<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/**
 * RN-PED-51 (resuelta DU-P15): el texto está en la tarea, en con quién / para quién o en las aclaraciones
 * (no en los comentarios). Sin distinguir mayúsculas ni tildes (collation `utf8mb4_unicode_ci`), con
 * `LIKE` parametrizado y los comodines escapados.
 */
final readonly class PorTexto implements FiltroPedidos
{
    public function __construct(private string $texto) {}

    public function aplicar(Builder $query): void
    {
        $patron = '%'.addcslashes(trim($this->texto), '\%_').'%';

        $query->where(fn (Builder $q) => $q->where('pedidos.tarea', 'like', $patron)
            ->orWhere('pedidos.con_quien', 'like', $patron)
            ->orWhere('pedidos.aclaraciones', 'like', $patron));
    }
}
