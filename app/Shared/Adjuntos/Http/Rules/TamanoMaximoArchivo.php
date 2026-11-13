<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * "El archivo pesa N MB y el máximo es 20 MB." (HU-025 criterio 3). El máximo sale de `adjuntos.max_mb`.
 */
final readonly class TamanoMaximoArchivo implements ValidationRule
{
    private const BYTES_POR_MB = 1048576;

    public function __construct(private int $maximoMb) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $bytes = (int) $value->getSize();

        if ($bytes > $this->maximoMb * self::BYTES_POR_MB) {
            $fail("El archivo pesa {$this->enMb($bytes)} MB y el máximo es {$this->maximoMb} MB.");
        }
    }

    private function enMb(int $bytes): string
    {
        $mb = number_format($bytes / self::BYTES_POR_MB, 1, ',', '.');

        return str_ends_with($mb, ',0') ? substr($mb, 0, -2) : $mb;
    }
}
