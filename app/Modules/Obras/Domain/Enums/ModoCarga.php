<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Enums;

/** Cómo se cargó lo presupuestado de un presupuesto (C16, RN-OBR-31): o por rubro o solo el total. */
enum ModoCarga: string
{
    case SinCargar = 'sin_cargar';
    case PorRubro = 'por_rubro';
    case SoloTotal = 'solo_total';
}
