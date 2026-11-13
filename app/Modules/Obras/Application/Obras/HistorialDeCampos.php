<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Antes → después de los campos de un modelo para el historial (ADR-010): fechas `YYYY-MM-DD`,
 * enums por su valor, vacío como null. Solo deja los campos que cambiaron.
 */
final class HistorialDeCampos
{
    private const FORMATO_FECHA = 'Y-m-d';

    /**
     * @param  list<string>  $campos
     * @return array<string, scalar|null>
     */
    public static function valores(Model $modelo, array $campos): array
    {
        $valores = [];

        foreach ($campos as $campo) {
            $valores[$campo] = self::escalar($modelo->getAttribute($campo));
        }

        return $valores;
    }

    /**
     * @param  array<string, scalar|null>  $antes
     * @param  array<string, scalar|null>  $despues
     * @return array<string, array{antes: scalar|null, despues: scalar|null}>
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

    /**
     * @return scalar|null
     */
    public static function escalar(mixed $valor): string|int|float|bool|null
    {
        return match (true) {
            $valor instanceof DateTimeInterface => $valor->format(self::FORMATO_FECHA),
            $valor instanceof BackedEnum => $valor->value,
            $valor === '' => null,
            is_scalar($valor) || $valor === null => $valor,
            default => null,
        };
    }
}
