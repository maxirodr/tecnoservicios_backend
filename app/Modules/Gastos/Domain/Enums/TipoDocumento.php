<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Enums;

/** W-10 "Factura / Sin documento". No hay nota de crédito (resuelta DU-G05). */
enum TipoDocumento: string
{
    case Factura = 'factura';
    case SinDocumento = 'sin_documento';
}
