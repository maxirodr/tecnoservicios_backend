<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un link solo se acepta si es http:// o https:// con un dominio de verdad (HU-025 criterio 4):
 * nada de `javascript:`, `file:`, `ftp:`, `data:`, texto suelto ni dominios sin punto ("https://hola").
 * Los links con tildes o eñe (carpetas de OneDrive en castellano) se aceptan: se validan normalizados
 * (`normalizar`, como la barra del navegador) y se guardan como los escribió la persona, legibles
 * (PU-129). El mensaje dice el motivo real del rechazo (PU-010, PU-026).
 */
final class LinkHttp implements ValidationRule
{
    public const MENSAJE = 'El link tiene que empezar con http:// o https://.';

    public const CON_ESPACIOS = 'El link no puede tener espacios. Copialo desde la barra del navegador.';

    public const NO_PARECE_LINK = 'Eso no parece un link. Copialo desde la barra del navegador.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $motivo = is_string($value) ? self::motivoDeRechazo($value) : self::MENSAJE;

        if ($motivo !== null) {
            $fail($motivo);
        }
    }

    public static function esValido(string $url): bool
    {
        return self::motivoDeRechazo($url) === null;
    }

    public static function motivoDeRechazo(string $url): ?string
    {
        if (preg_match('#^https?://#i', $url) !== 1) {
            return self::MENSAJE;
        }

        if (preg_match('/\s/u', $url) === 1) {
            return self::CON_ESPACIOS;
        }

        $normalizado = self::normalizar($url);
        $host = parse_url($normalizado, PHP_URL_HOST);

        if (filter_var($normalizado, FILTER_VALIDATE_URL) === false || ! is_string($host) || ! str_contains(trim($host, '.'), '.')) {
            return self::NO_PARECE_LINK;
        }

        return null;
    }

    /**
     * El dominio con eñe o tildes pasa a punycode y el resto de los caracteres no ASCII se codifica
     * (`ñ` → `%C3%B1`), como lo hace la barra del navegador. Lo que ya es ASCII no se toca.
     */
    public static function normalizar(string $url): string
    {
        if (preg_match('#^(https?://)([^/?\#]*)(.*)$#isu', $url, $partes) !== 1) {
            return $url;
        }

        [, $esquema, $autoridad, $resto] = $partes;

        return $esquema.self::autoridadAscii($autoridad).self::codificarNoAscii($resto);
    }

    private static function autoridadAscii(string $autoridad): string
    {
        if (preg_match('/^(.*@)?([^:@]*)(:\d*)?$/su', $autoridad, $partes) !== 1) {
            return self::codificarNoAscii($autoridad);
        }

        $host = $partes[2];
        if (preg_match('/[^\x00-\x7F]/', $host) === 1) {
            $ascii = idn_to_ascii(mb_strtolower($host), IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            $host = $ascii === false ? $host : $ascii;
        }

        return self::codificarNoAscii($partes[1]).$host.($partes[3] ?? '');
    }

    private static function codificarNoAscii(string $texto): string
    {
        return (string) preg_replace_callback(
            '/[^\x00-\x7F]+/u',
            static fn (array $m): string => rawurlencode($m[0]),
            $texto,
        );
    }
}
