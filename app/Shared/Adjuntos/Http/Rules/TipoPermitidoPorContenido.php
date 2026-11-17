<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http\Rules;

use App\Shared\Adjuntos\DetectorTipoArchivo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * El tipo se valida por contenido (HU-025 criterio 2): un `.exe` renombrado a `.pdf` se rechaza.
 * El mensaje dice el motivo real: archivo vacío (PU-011), extensión que miente sobre el contenido o
 * tipo no aceptado, con el mismo texto que usa el front (PU-030).
 */
final readonly class TipoPermitidoPorContenido implements ValidationRule
{
    public const MENSAJE = 'Ese tipo de archivo no se puede subir. Se aceptan PDF, imágenes (JPG, PNG, WEBP, HEIC), Excel, Word, CSV y TXT.';

    public const VACIO = 'El archivo está vacío. Elegí otro.';

    /** Extensiones aceptadas y cómo se las nombra en el mensaje. */
    private const TIPOS_POR_EXTENSION = [
        'pdf' => 'PDF', 'jpg' => 'JPG', 'jpeg' => 'JPG', 'png' => 'PNG', 'webp' => 'WEBP', 'heic' => 'HEIC',
        'heif' => 'HEIF', 'xlsx' => 'Excel', 'xls' => 'Excel', 'docx' => 'Word', 'doc' => 'Word',
        'csv' => 'CSV', 'txt' => 'TXT',
    ];

    public function __construct(private DetectorTipoArchivo $detector) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(self::MENSAJE);

            return;
        }

        if ((int) $value->getSize() === 0) {
            $fail(self::VACIO);

            return;
        }

        if ($this->detector->detectar($value->getPathname(), $value->getClientOriginalName()) === null) {
            $fail(self::mensajeParaRechazo($value->getClientOriginalName()));
        }
    }

    public static function mensajeParaRechazo(string $nombreOriginal): string
    {
        $extension = mb_strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $tipo = self::TIPOS_POR_EXTENSION[$extension] ?? null;

        return $tipo === null
            ? self::MENSAJE
            : "Este archivo no es un {$tipo} aunque termine en .{$extension}. Elegí otro.";
    }
}
