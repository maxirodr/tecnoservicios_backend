<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use Illuminate\Pagination\LengthAwarePaginator;

/** Resultado de `ListarObrasQuery`: la página de la pestaña + `meta` de W-05. */
final readonly class ListadoObras
{
    /**
     * @param  LengthAwarePaginator<int, FilaObra>  $pagina
     * @param  array<string, int>  $contadores
     */
    public function __construct(
        public LengthAwarePaginator $pagina,
        public array $contadores,
        public ?int $pedidosSinObraAbiertos,
    ) {}
}
