<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Rendicion\Domain\Models\Material;

/**
 * Qué cambió de un material, para `actividades` (F-14, ADR-010): quién, cuándo y antes → después de cada campo
 * que se ve en W-48, como el presupuesto por rubro (resuelta DU-09).
 */
final class HistorialDeMaterial
{
    private const CAMPOS = ['nombre', 'unidad', 'cantidad_presupuestada', 'precio_presupuestado', 'presupuesto_id', 'fuera_de_presupuesto'];

    /**
     * @return array<string, mixed>
     */
    public static function foto(Material $material): array
    {
        $foto = [];
        foreach (self::CAMPOS as $campo) {
            $foto[$campo] = $material->getAttribute($campo);
        }

        return $foto;
    }

    /**
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    public static function alta(Material $material): array
    {
        return self::diferencias(array_fill_keys(self::CAMPOS, null), self::foto($material));
    }

    /**
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    public static function baja(Material $material): array
    {
        return self::diferencias(self::foto($material), array_fill_keys(self::CAMPOS, null));
    }

    /**
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $despues
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    public static function diferencias(array $antes, array $despues): array
    {
        $cambios = [];

        foreach (self::CAMPOS as $campo) {
            if (($antes[$campo] ?? null) !== ($despues[$campo] ?? null)) {
                $cambios[$campo] = ['antes' => $antes[$campo] ?? null, 'despues' => $despues[$campo] ?? null];
            }
        }

        return $cambios;
    }
}
