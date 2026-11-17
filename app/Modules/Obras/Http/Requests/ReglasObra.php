<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Domain\Enums\TipoContacto;
use App\Shared\Adjuntos\Http\Rules\LinkHttp;

/**
 * Reglas de los datos de la obra compartidas por el alta y la edición (HU-016 criterio 6: las mismas
 * validaciones). En la edición todo es opcional (`sometimes`) pero lo que viene no puede quedar vacío.
 * `estado` no está: si viene, se ignora (HU-012 c. 5, HU-016 c. 4).
 */
final class ReglasObra
{
    public const LARGO_NOMBRE_TRABAJO = 200;

    public const LARGO_CLIENTE = 150;

    private const FORMATO_FECHA = 'date_format:Y-m-d';

    /**
     * @return array<string, mixed>
     */
    public static function datos(bool $alta): array
    {
        $obligatorio = $alta ? 'required' : 'sometimes';

        return [
            'nombre_trabajo' => [$obligatorio, 'string', 'max:'.self::LARGO_NOMBRE_TRABAJO],
            'cliente' => [$obligatorio, 'string', 'max:'.self::LARGO_CLIENTE],
            'planta' => ['nullable', 'string', 'max:150'],
            'fecha_inicio_prevista' => ['nullable', self::FORMATO_FECHA],
            'fecha_fin_prevista' => ['nullable', self::FORMATO_FECHA],
            // Solo link (F-19, ADR-014): un id de adjunto o cualquier otra cosa da 422.
            'carpeta_drive_url' => ['nullable', 'string', 'max:500', new LinkHttp],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function contactos(bool $alta): array
    {
        $obligatorio = $alta ? 'required' : 'sometimes';
        $reglas = [];

        foreach (TipoContacto::cases() as $tipo) {
            $reglas["contactos.{$tipo->value}"] = [$obligatorio, 'array'];
            $reglas["contactos.{$tipo->value}.nombre"] = [$obligatorio, 'string', 'max:120'];
            $reglas["contactos.{$tipo->value}.email"] = [$obligatorio, 'string', 'email', 'max:190'];
            $reglas["contactos.{$tipo->value}.telefono"] = [$obligatorio, 'string', 'max:50'];
        }

        return $reglas;
    }
}
