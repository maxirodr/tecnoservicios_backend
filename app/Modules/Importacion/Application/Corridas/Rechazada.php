<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Importacion\Domain\Informe\Faltante;
use RuntimeException;

/**
 * Una obra o una fila que el módulo dueño rechazó al guardarla (RN-IMP-09): se deshace todo lo de ella (RN-IMP-07,
 * RN-IMP-19) y sus faltantes van al informe.
 */
final class Rechazada extends RuntimeException
{
    /**
     * @param  list<Faltante>  $faltantes
     */
    public function __construct(public readonly array $faltantes)
    {
        parent::__construct('Rechazada por el módulo dueño del dato.');
    }
}
