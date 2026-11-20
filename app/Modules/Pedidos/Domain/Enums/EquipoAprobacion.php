<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Enums;

/** Aprobación del arreglo (W-49): la marca una persona, el sistema no decide nada con ella (RN-PED-57). */
enum EquipoAprobacion: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Bloqueado = 'bloqueado';
}
