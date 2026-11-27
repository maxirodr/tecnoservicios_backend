<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application;

use Carbon\CarbonImmutable;

/** "Hoy" y la hora del informe en la zona de la empresa (`app.zona_empresa`), nunca en UTC. */
final class FechaEmpresa
{
    public static function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now(config()->string('app.zona_empresa'));
    }

    /** "2026-11-30" */
    public static function hoy(): string
    {
        return self::ahora()->toDateString();
    }
}
