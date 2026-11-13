<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;

/**
 * RN-OBR-15: el N.º sugerido para un adicional es el del principal + el siguiente sufijo libre
 * (`2-131-2026 A1`, después `A2`). Libre en todo el sistema, porque el N.º es único (RN-OBR-12).
 */
final class SugerirNumeroAdicionalQuery
{
    private const SUFIJO_MAXIMO = 99;

    public function handle(Obra $obra): ?string
    {
        $base = Presupuesto::query()->where('obra_id', $obra->id)->where('es_principal', true)->value('numero');

        if (! is_string($base)) {
            return null;
        }

        $base = explode(' ', $base)[0];
        $usados = Presupuesto::query()->where('numero', 'like', "{$base} A%")->pluck('numero')->all();

        for ($sufijo = 1; $sufijo <= self::SUFIJO_MAXIMO; $sufijo++) {
            $candidato = "{$base} A{$sufijo}";

            if (! in_array($candidato, $usados, true)) {
                return $candidato;
            }
        }

        return null;
    }
}
