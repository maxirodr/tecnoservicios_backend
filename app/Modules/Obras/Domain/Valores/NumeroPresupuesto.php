<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Valores;

/**
 * N.º de presupuesto `P-NNN-AAAA` con sufijo de adicional opcional ` A1` (RN-OBR-10), con la
 * validación tolerante de RN-OBR-11: separadores libres, `NNN` con al menos 3 dígitos, sufijo en
 * mayúscula y la revisión escrita en el número se separa (`revisionEscrita`).
 */
final readonly class NumeroPresupuesto
{
    public const ANIO_MINIMO = 2000;

    private const DIGITOS_MINIMOS = 3;

    private const DIGITOS_MAXIMOS = 4;

    private const DIGITOS_ANIO = 4;

    private const PATRON_REVISION = '/[\s\-_]*REV[\s\-_]*\d{1,2}$/';

    private const PATRON_SUFIJO = '/[\s\-_]*A(\d{1,2})$/';

    private const SEPARADOR = '[\s\-\/._]+';

    private function __construct(
        public string $base,
        public ?int $sufijo,
        public ?Revision $revisionEscrita,
    ) {}

    /**
     * @throws ValorInvalido
     */
    public static function desde(string $escrito, int $anioActual): self
    {
        $resto = mb_strtoupper(trim($escrito));
        $revision = null;

        if (preg_match(self::PATRON_REVISION, $resto, $match) === 1) {
            $revision = Revision::desde(trim($match[0], " -_\t"));
            $resto = trim(substr($resto, 0, -strlen($match[0])));
        }

        $sufijo = null;

        if (preg_match(self::PATRON_SUFIJO, $resto, $match) === 1 && (int) $match[1] > 0) {
            $sufijo = (int) $match[1];
            $resto = trim(substr($resto, 0, -strlen($match[0])));
        }

        return new self(self::base($resto, $anioActual), $sufijo, $revision);
    }

    public function conSufijo(int $sufijo): self
    {
        return new self($this->base, $sufijo, null);
    }

    /** Forma guardada y mostrada: `2-131-2026` o `2-131-2026 A1` (sin la revisión). */
    public function valor(): string
    {
        return $this->sufijo === null ? $this->base : "{$this->base} A{$this->sufijo}";
    }

    public function __toString(): string
    {
        return $this->valor();
    }

    /**
     * @throws ValorInvalido
     */
    private static function base(string $resto, int $anioActual): string
    {
        $sep = self::SEPARADOR;

        if (preg_match('/^(\d)'.$sep.'(\d+)'.$sep.'(\d+)$/', $resto, $partes) !== 1) {
            throw new ValorInvalido('Escribí solo el número, por ejemplo 2-131-2026.');
        }

        [, $prefijo, $numero, $anio] = $partes;

        if (strlen($numero) > self::DIGITOS_MAXIMOS) {
            throw new ValorInvalido('Eso parecen dos presupuestos: cargá uno como principal y el otro como adicional.');
        }

        if (strlen($anio) !== self::DIGITOS_ANIO) {
            throw new ValorInvalido('Escribí el año con 4 dígitos, por ejemplo 2-131-2026.');
        }

        if ((int) $anio < self::ANIO_MINIMO || (int) $anio > $anioActual + 1) {
            throw new ValorInvalido('El año tiene que estar entre '.self::ANIO_MINIMO.' y '.($anioActual + 1).'.');
        }

        return $prefijo.'-'.str_pad($numero, self::DIGITOS_MINIMOS, '0', STR_PAD_LEFT).'-'.$anio;
    }
}
