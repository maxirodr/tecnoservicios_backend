<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Equipos;

use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use BackedEnum;
use DateTimeInterface;

/** Antes → después de los datos de un equipo, para `actividades` (RN-PED-58, ADR-010). */
final class CamposDelEquipo
{
    public const CAMPOS = ['equipo', 'codigo', 'trabajo', 'taller', 'estado', 'aprobacion', 'fecha', 'notas'];

    /**
     * @return array<string, string|null>
     */
    public static function valores(?EquipoReparacion $equipo): array
    {
        $valores = [];

        foreach (self::CAMPOS as $campo) {
            $valor = $equipo?->getAttribute($campo);
            $valores[$campo] = match (true) {
                $valor instanceof DateTimeInterface => $valor->format('Y-m-d'),
                $valor instanceof BackedEnum => (string) $valor->value,
                is_string($valor) && $valor !== '' => $valor,
                default => null,
            };
        }

        return $valores;
    }

    /**
     * @param  array<string, string|null>  $antes
     * @param  array<string, string|null>  $despues
     * @return array<string, array{antes: string|null, despues: string|null}>
     */
    public static function diferencias(array $antes, array $despues): array
    {
        $cambios = [];

        foreach ($despues as $campo => $valor) {
            if (($antes[$campo] ?? null) !== $valor) {
                $cambios[$campo] = ['antes' => $antes[$campo] ?? null, 'despues' => $valor];
            }
        }

        return $cambios;
    }
}
