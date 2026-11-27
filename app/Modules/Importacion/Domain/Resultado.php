<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain;

/** Qué pasó con cada obra, gastado, retirado, tarea o documento (`importacion_filas.resultado`, §11.3). */
enum Resultado: string
{
    case Importada = 'importada';
    case YaEstaba = 'ya_estaba';
    case Faltante = 'faltante';
    case NoSeImporta = 'no_se_importa';
}
