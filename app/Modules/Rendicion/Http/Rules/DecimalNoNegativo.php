<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Cantidad (`DECIMAL(14,3)`) o precio (`DECIMAL(15,2)`) de la lista de materiales (ADR-008, rendicion.md §4.2):
 * string decimal ≥ 0 con hasta `escala` decimales. Un número JSON entero también se acepta; uno con
 * decimales, no (float).
 */
final readonly class DecimalNoNegativo implements ValidationRule
{
    private const PATRON = '/^-?\d+(\.\d+)?$/';

    public function __construct(
        private string $que,
        private string $ejemplo,
        private int $escala,
        private int $enteros,
        private string $negativo,
    ) {}

    public static function cantidad(): self
    {
        return new self('la cantidad', '12.5', 3, 11, 'negativa');
    }

    public static function precio(): self
    {
        return new self('el precio', '9800.50', 2, 13, 'negativo');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $texto = self::texto($value);

        if ($texto === '' || is_float($value) || preg_match(self::PATRON, $texto) !== 1) {
            $fail('Escribí '.$this->que.' como número, por ejemplo '.$this->ejemplo.'.');

            return;
        }

        [$enteros, $decimales] = array_pad(explode('.', ltrim($texto, '-')), 2, '');

        if (strlen($decimales) > $this->escala) {
            $fail(ucfirst($this->que)." lleva como máximo {$this->escala} decimales.");
        } elseif (str_starts_with($texto, '-')) {
            $fail(ucfirst($this->que)." no puede ser {$this->negativo}.");
        } elseif (strlen(ltrim($enteros, '0')) > $this->enteros) {
            $fail(ucfirst($this->que).' es demasiado grande.');
        }
    }

    public static function texto(mixed $value): string
    {
        return is_string($value) || is_int($value) ? trim((string) $value) : '';
    }
}
