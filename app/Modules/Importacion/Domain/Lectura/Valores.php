<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Lectura;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Lectura tolerante de las celdas, sin adivinar (`importacion.md` §5 "Formatos comunes", RN-IMP-14): espacios al
 * borde y dobles fuera; fechas de Excel o `dd/mm/aaaa`; importes y cantidades como número de Excel o con punto de
 * miles y coma decimal ("1.250.000,50"), a texto decimal sin pasar por `float` (ADR-008). Lo que no se entiende, o
 * trae más decimales de los permitidos, es `DatoInvalido` con el texto del informe: nunca se redondea.
 */
final class Valores
{
    public const DECIMALES_IMPORTE = 2;

    public const DECIMALES_CANTIDAD = 3;

    /** Día 0 de las fechas de Excel (sistema 1900, con su 29-feb-1900 inexistente ya descontado). */
    private const EXCEL_DIA_CERO = '1899-12-30';

    private const PRECISION_MAXIMA = 17;

    private const PRECISION_MINIMA = 15;

    private const NUMERO_ARGENTINO = '/^(-?)(\d{1,3}(?:\.\d{3})+|\d+)(?:,(\d+))?$/';

    private const LINK = '/^https?:\/\/\S+$/i';

    public static function texto(string|int|float|bool|DateTimeInterface|null $valor): ?string
    {
        $texto = match (true) {
            $valor === null => '',
            $valor instanceof DateTimeInterface => $valor->format('d/m/Y'),
            is_bool($valor) => $valor ? 'Sí' : 'No',
            is_float($valor) => self::numeroExacto($valor),
            default => (string) $valor,
        };
        $lineas = array_map(static fn (string $l): string => trim((string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $l)), explode("\n", str_replace("\r", '', $texto)));
        $limpio = trim(implode("\n", $lineas));

        return $limpio === '' ? null : $limpio;
    }

    /** Para comparar listas y nombres: sin mayúsculas, tildes ni espacios de más ("en ejecucion" = "En ejecución"). */
    public static function clave(?string $texto): string
    {
        return Str::of((string) $texto)->ascii()->lower()->squish()->toString();
    }

    /**
     * @return string|null "AAAA-MM-DD"
     *
     * @throws DatoInvalido
     */
    public static function fecha(string|int|float|bool|DateTimeInterface|null $valor): ?string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        $texto = self::texto($valor);

        if ($texto === null) {
            return null;
        }

        if ((is_int($valor) || is_float($valor)) && $valor > 0 && floor($valor) === (float) $valor) {
            return (new DateTimeImmutable(self::EXCEL_DIA_CERO))->modify('+'.(int) $valor.' days')->format('Y-m-d');
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $texto, $p) !== 1 || ! checkdate((int) $p[2], (int) $p[1], (int) $p[3])) {
            throw new DatoInvalido("La fecha '{$texto}' no existe.");
        }

        return sprintf('%04d-%02d-%02d', (int) $p[3], (int) $p[2], (int) $p[1]);
    }

    /**
     * @return numeric-string|null con 2 decimales
     *
     * @throws DatoInvalido
     */
    public static function importe(string|int|float|bool|DateTimeInterface|null $valor): ?string
    {
        return self::decimal($valor, self::DECIMALES_IMPORTE, 'El importe');
    }

    /**
     * @return numeric-string|null con 3 decimales
     *
     * @throws DatoInvalido
     */
    public static function cantidad(string|int|float|bool|DateTimeInterface|null $valor): ?string
    {
        return self::decimal($valor, self::DECIMALES_CANTIDAD, 'La cantidad');
    }

    /**
     * @throws DatoInvalido
     */
    public static function link(string $texto): string
    {
        if (preg_match(self::LINK, $texto) !== 1) {
            throw new DatoInvalido('El link tiene que empezar con http:// o https://.');
        }

        return $texto;
    }

    public static function esMail(string $texto): bool
    {
        return filter_var($texto, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** "si", "sí", "no" (§5 "Formatos comunes"); null si dice otra cosa o está vacía. */
    public static function siNo(?string $texto): ?bool
    {
        return match (self::clave($texto)) {
            'si' => true,
            'no' => false,
            default => null,
        };
    }

    /**
     * @return numeric-string|null
     *
     * @throws DatoInvalido
     */
    private static function decimal(string|int|float|bool|DateTimeInterface|null $valor, int $escala, string $que): ?string
    {
        $texto = self::texto($valor);

        if ($texto === null) {
            return null;
        }

        $numero = is_int($valor) || is_float($valor) ? $texto : self::desdeTextoArgentino($texto);

        if ($numero === null || ! is_numeric($numero) || preg_match('/^-?\d+(\.\d+)?$/', $numero) !== 1) {
            throw new DatoInvalido("{$que} '{$texto}' no se entiende.");
        }

        $punto = strpos($numero, '.');

        if ($punto !== false && strlen($numero) - $punto - 1 > $escala) {
            throw new DatoInvalido("{$que} '{$texto}' tiene más de {$escala} decimales.");
        }

        return bcadd($numero, '0', $escala);
    }

    /** "1.250.000,50" → "1250000.50"; "45500,5" → "45500.5"; sin signo $ ni otro texto. */
    private static function desdeTextoArgentino(string $texto): ?string
    {
        if (preg_match(self::NUMERO_ARGENTINO, str_replace(' ', '', $texto), $p) !== 1) {
            return null;
        }

        $decimales = $p[3] ?? '';

        return $p[1].str_replace('.', '', $p[2]).($decimales === '' ? '' : ".{$decimales}");
    }

    /**
     * El número que guardó Excel, con la representación más corta que vuelve al mismo valor (45500.5, no
     * 45500.500000000001): así un importe con 3 decimales se ve como tal y no se redondea en silencio.
     */
    private static function numeroExacto(float $valor): string
    {
        for ($precision = self::PRECISION_MINIMA; $precision < self::PRECISION_MAXIMA; $precision++) {
            $texto = sprintf("%.{$precision}G", $valor);

            if ((float) $texto === $valor) {
                break;
            }
        }

        $texto = sprintf("%.{$precision}G", $valor);

        return str_contains($texto, 'E') ? number_format($valor, self::PRECISION_MAXIMA, '.', '') : $texto;
    }
}
