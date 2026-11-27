<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Ports;

use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lectura\PlantillaInvalida;
use App\Modules\Importacion\Domain\Plantilla\Hoja;

/**
 * Repositorio de lectura de las plantillas `.xlsx` (`arquitectura.md` §3, `importacion.md` §11.2): hojas y columnas
 * por su nombre (el orden no importa, las de más se ignoran), desde la fila 2, sin las filas totalmente vacías.
 */
interface LectorDePlantillas
{
    /**
     * @param  list<Hoja>  $hojas
     * @return array<string, list<FilaLeida>> por nombre de hoja; una hoja opcional que no vino, vacía
     *
     * @throws PlantillaInvalida
     */
    public function leer(string $ruta, array $hojas): array;
}
