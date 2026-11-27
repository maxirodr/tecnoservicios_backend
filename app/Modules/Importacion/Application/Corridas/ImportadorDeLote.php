<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lote;

/** Lo propio de cada lote (obras, tareas): qué hace con las hojas y cómo lo cuenta en la consola (§8). */
interface ImportadorDeLote
{
    public function lote(): Lote;

    /**
     * @param  array<string, list<FilaLeida>>  $hojas
     * @return array<string, int> solo conteos (van a `importacion_corridas.resumen`, RN-IMP-13)
     */
    public function correr(array $hojas, Contexto $ctx): array;

    /**
     * La parte de la consola después de "Ensayo: no se guardó nada." o "Carga confirmada (aprobó …)."
     *
     * @param  array<string, int>  $conteos
     */
    public function mensaje(array $conteos, Contexto $ctx, string $informe): string;
}
