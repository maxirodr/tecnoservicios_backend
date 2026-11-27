<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Lectura;

use DomainException;

/** La corrida no empieza (RN-IMP-11): "Falta la columna 'Fecha de corte' en la hoja Obras." */
final class PlantillaInvalida extends DomainException
{
    public static function faltaHoja(string $hoja): self
    {
        return new self("Falta la hoja {$hoja}.");
    }

    public static function faltaColumna(string $columna, string $hoja): self
    {
        return new self("Falta la columna '{$columna}' en la hoja {$hoja}.");
    }
}
