<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Ports;

use App\Modules\Importacion\Domain\Plantilla\Hoja;

/** La plantilla vacía (`importacion:plantilla`, §5): encabezados, listas desplegables y la hoja "Cómo completar". */
interface EscritorDePlantilla
{
    /**
     * @param  list<Hoja>  $hojas
     * @param  list<array{string, string}>  $comoCompletar  [columna, cómo se completa]
     */
    public function escribir(array $hojas, array $comoCompletar, string $ruta): void;
}
