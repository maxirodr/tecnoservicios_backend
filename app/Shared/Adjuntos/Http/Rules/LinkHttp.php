<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un link solo se acepta si es http:// o https:// con dominio (HU-025 criterio 4):
 * nada de `javascript:`, `file:`, `ftp:`, `data:` ni texto suelto.
 */
final class LinkHttp implements ValidationRule
{
    public const MENSAJE = 'El link tiene que empezar con http:// o https://.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::esValido($value)) {
            $fail(self::MENSAJE);
        }
    }

    public static function esValido(string $url): bool
    {
        if (preg_match('#^https?://#i', $url) !== 1 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '';
    }
}
