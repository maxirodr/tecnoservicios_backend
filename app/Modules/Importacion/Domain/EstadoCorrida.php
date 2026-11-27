<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain;

/** `importacion_corridas.estado` (§11.3, RN-IMP-07). */
enum EstadoCorrida: string
{
    case EnCurso = 'en_curso';
    case Terminada = 'terminada';
    case ConErrores = 'con_errores';
    case Interrumpida = 'interrumpida';
}
