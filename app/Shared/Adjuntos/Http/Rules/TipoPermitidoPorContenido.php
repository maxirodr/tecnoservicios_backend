<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http\Rules;

use App\Shared\Adjuntos\DetectorTipoArchivo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * El tipo se valida por contenido (HU-025 criterio 2): un `.exe` renombrado a `.pdf` se rechaza.
 */
final readonly class TipoPermitidoPorContenido implements ValidationRule
{
    public const MENSAJE = 'Ese tipo de archivo no se acepta. Subí un PDF, una imagen, un Excel, un Word, un CSV o un TXT.';

    public function __construct(private DetectorTipoArchivo $detector) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile
            || $this->detector->detectar($value->getPathname(), $value->getClientOriginalName()) === null) {
            $fail(self::MENSAJE);
        }
    }
}
