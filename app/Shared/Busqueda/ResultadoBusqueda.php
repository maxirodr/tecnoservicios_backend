<?php

declare(strict_types=1);

namespace App\Shared\Busqueda;

/**
 * Lo que devuelve una fuente: cuántos encontró en total y los primeros, ya en el formato que muestra
 * W-18 (cada uno con su `id` para abrir la ficha).
 */
final readonly class ResultadoBusqueda
{
    /**
     * @param  list<array<string, mixed>>  $resultados
     */
    public function __construct(
        public int $cantidad,
        public array $resultados,
    ) {}
}
