<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Shared\Http\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * Junta los 422 de las reglas de negocio para devolverlos todos juntos, cada uno en su campo; los de un
 * renglón como `renglones.2.rubro` con "Renglón 3: elegí el rubro." (RN-GAS-09).
 */
final class Errores
{
    private const INVALIDO = 422;

    /** @var array<string, list<string>> */
    private array $errores = [];

    public function campo(string $campo, string $mensaje): void
    {
        $this->errores[$campo] ??= [];

        if (! in_array($mensaje, $this->errores[$campo], true)) {
            $this->errores[$campo][] = $mensaje;
        }
    }

    public function renglon(int $posicion, string $campo, string $mensaje): void
    {
        $this->campo("renglones.{$posicion}.{$campo}", self::delRenglon($posicion, $mensaje));
    }

    public static function delRenglon(int $posicion, string $mensaje): string
    {
        $segunda = mb_substr($mensaje, 1, 1);
        // "Elegí el rubro." → "elegí el rubro."; una sigla ("FIJOS y HORAS…") queda como está.
        $inicio = $segunda !== '' && mb_strtoupper($segunda) === $segunda && mb_strtolower($segunda) !== $segunda
            ? mb_substr($mensaje, 0, 1)
            : mb_strtolower(mb_substr($mensaje, 0, 1));

        return 'Renglón '.($posicion + 1).': '.$inicio.mb_substr($mensaje, 1);
    }

    /**
     * 422 con el texto como mensaje y, en `errors.existente`, lo ya cargado para abrirlo (RN-GAS-08, 23).
     *
     * @param  array<string, mixed>  $existente
     */
    public static function yaCargado(string $campo, string $mensaje, array $existente): HttpResponseException
    {
        return new HttpResponseException(ApiResponse::error($mensaje, self::INVALIDO, [$campo => [$mensaje], 'existente' => $existente]));
    }

    public function hay(): bool
    {
        return $this->errores !== [];
    }

    public function tiene(string $campo): bool
    {
        return isset($this->errores[$campo]);
    }

    /**
     * @throws ValidationException
     */
    public function lanzarSiHay(): void
    {
        if ($this->hay()) {
            throw ValidationException::withMessages($this->errores);
        }
    }
}
