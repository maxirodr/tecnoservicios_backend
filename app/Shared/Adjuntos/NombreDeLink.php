<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

/**
 * Nombre de un link pegado sin "Nombre para mostrar" (PU-112, PU-207): la última parte con sentido del
 * camino, decodificada ("https://drive.example/Obras 2026/oferta%20año.pdf" → "oferta año.pdf"), y si no
 * hay camino, el dominio legible ("xn--and-6ma2c.example" → "ñandú.example"). Las partes finales que no
 * dicen qué es el archivo (`/file/d/<id>/view`) se saltean.
 *
 * Es la misma regla que `parteFinalDelLink` del front (`src/shared/adjuntos/nombreDeLink.ts`): la ficha,
 * la tabla y el historial nombran igual al link.
 */
final class NombreDeLink
{
    /** Igual que `GENERICAS` del front. */
    private const GENERICAS = ['view', 'edit', 'preview', 'open', 'share', 'sharing', 'download'];

    /** Largo de `adjuntos.nombre`. */
    public const LARGO_MAXIMO = 200;

    public static function de(string $url): string
    {
        $camino = parse_url($url, PHP_URL_PATH);
        $partes = is_string($camino) ? explode('/', $camino) : [];

        foreach (array_reverse($partes) as $parte) {
            $parte = self::recortar(self::decodificar($parte));
            if ($parte !== '' && ! in_array(mb_strtolower($parte), self::GENERICAS, true)) {
                return mb_substr($parte, 0, self::LARGO_MAXIMO);
            }
        }

        return self::dominio($url);
    }

    /** El dominio legible aunque el link venga codificado ("xn--…" → "ñandú.example"). */
    public static function dominio(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $legible = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return $legible === false || $legible === '' ? $host : $legible;
    }

    /**
     * Como `decodeURIComponent` del front: si hay un `%` suelto o los bytes no forman UTF-8, la parte
     * queda como está ("100%25-ok%" → "100%25-ok%").
     */
    private static function decodificar(string $parte): string
    {
        if (preg_match('/%(?![0-9A-Fa-f]{2})/', $parte) === 1) {
            return $parte;
        }

        $decodificada = rawurldecode($parte);

        return mb_check_encoding($decodificada, 'UTF-8') ? $decodificada : $parte;
    }

    /** Como `trim()` de JS: también saca los espacios Unicode. */
    private static function recortar(string $texto): string
    {
        return (string) preg_replace('/^\s+|\s+$/u', '', $texto);
    }
}
