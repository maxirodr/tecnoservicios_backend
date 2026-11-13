<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure;

use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;

/** Hasta la semana 3 no hay rendición: todo "—" (RN-OBR-04). Rendicion registra el suyo. */
final class AvanceGastoDeObraNulo implements AvanceGastoDeObra
{
    public function porcentajesGastados(array $obraIds): array
    {
        return array_fill_keys($obraIds, null);
    }

    public function pasadas(array $obraIds): array
    {
        return array_fill_keys($obraIds, false);
    }
}
