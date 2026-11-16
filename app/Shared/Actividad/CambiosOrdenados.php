<?php

declare(strict_types=1);

namespace App\Shared\Actividad;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JsonException;

/**
 * Guarda `cambios` como lista `[{campo, antes, despues}]` en lugar de un objeto JSON.
 *
 * MySQL normaliza el orden de las claves de una columna JSON (las ordena por largo y
 * después alfabéticamente), así que un objeto vuelve en otro orden que el del cambio y el
 * historial se muestra desordenado. Los arrays JSON sí conservan su orden.
 * Hacia afuera el atributo sigue siendo `array<campo, {antes, despues}>` en el orden original.
 *
 * @implements CastsAttributes<array<string, array{antes: mixed, despues: mixed}>|null, array<string, array{antes: mixed, despues: mixed}>|null>
 */
final class CambiosOrdenados implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, array{antes: mixed, despues: mixed}>|null
     *
     * @throws JsonException
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decodificado = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decodificado)) {
            return null;
        }

        if (! array_is_list($decodificado)) {
            /** @var array<string, array{antes: mixed, despues: mixed}> $decodificado */
            return $decodificado;
        }

        $cambios = [];
        foreach ($decodificado as $linea) {
            if (is_array($linea) && isset($linea['campo']) && is_string($linea['campo'])) {
                $cambios[$linea['campo']] = ['antes' => $linea['antes'] ?? null, 'despues' => $linea['despues'] ?? null];
            }
        }

        return $cambios;
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>|null  $value
     * @param  array<string, mixed>  $attributes
     *
     * @throws JsonException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        $lineas = [];
        foreach ($value as $campo => $cambio) {
            $lineas[] = ['campo' => (string) $campo, 'antes' => $cambio['antes'] ?? null, 'despues' => $cambio['despues'] ?? null];
        }

        return json_encode($lineas, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
}
