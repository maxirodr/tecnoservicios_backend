<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Rules;

use App\Shared\Domain\Importe;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Importe en pesos (ADR-008, obras.md §4.7): string decimal con hasta 2 decimales, sin negativos y
 * dentro de `DECIMAL(15,2)`. Un número JSON entero también se acepta; uno con decimales, no (float).
 */
final class ImporteValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $texto = self::texto($value);

        if ($texto === '' || ! Importe::esDecimal($texto) || is_float($value)) {
            $fail('Escribí el monto como número, por ejemplo 1500000.50.');

            return;
        }

        if (Importe::decimales($texto) > Importe::ESCALA) {
            $fail('El monto lleva como máximo 2 decimales.');

            return;
        }

        $importe = Importe::desde($texto);

        if ($importe->esNegativo()) {
            $fail('El monto no puede ser negativo.');
        } elseif ($importe->superaElMaximo()) {
            $fail('El monto es demasiado grande.');
        }
    }

    public static function texto(mixed $value): string
    {
        return is_string($value) || is_int($value) ? trim((string) $value) : '';
    }
}
