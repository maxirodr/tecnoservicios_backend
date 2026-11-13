<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use InvalidArgumentException;

/**
 * Importe en pesos, neto sin IVA, escala 2 (ADR-008). Nunca float: se opera con bcmath y viaja
 * como string decimal ("1500000.50"). Entra en `DECIMAL(15,2)`.
 */
final readonly class Importe
{
    public const ESCALA = 2;

    /** 13 enteros + 2 decimales = DECIMAL(15,2). */
    public const MAXIMO = '9999999999999.99';

    private const PATRON = '/^-?\d+(\.\d+)?$/';

    /**
     * @param  numeric-string  $valor
     */
    private function __construct(public string $valor) {}

    /**
     * @throws InvalidArgumentException si no es un decimal con hasta 2 decimales
     */
    public static function desde(string $escrito): self
    {
        $limpio = trim($escrito);

        if (! is_numeric($limpio) || ! self::esDecimal($limpio) || self::decimales($limpio) > self::ESCALA) {
            throw new InvalidArgumentException("Importe inválido: {$escrito}");
        }

        return new self(bcadd($limpio, '0', self::ESCALA));
    }

    public static function cero(): self
    {
        return new self(bcadd('0', '0', self::ESCALA));
    }

    public static function esDecimal(string $valor): bool
    {
        return preg_match(self::PATRON, trim($valor)) === 1;
    }

    public static function decimales(string $valor): int
    {
        $punto = strpos($valor, '.');

        return $punto === false ? 0 : strlen($valor) - $punto - 1;
    }

    public function sumar(self $otro): self
    {
        return new self(bcadd($this->valor, $otro->valor, self::ESCALA));
    }

    public function esNegativo(): bool
    {
        return bccomp($this->valor, '0', self::ESCALA) < 0;
    }

    public function superaElMaximo(): bool
    {
        return bccomp($this->valor, self::MAXIMO, self::ESCALA) > 0;
    }

    public function equals(self $otro): bool
    {
        return bccomp($this->valor, $otro->valor, self::ESCALA) === 0;
    }

    /**
     * Como lo lee la persona, igual que `formatearImporte` del front (`Intl.NumberFormat('es-AR')`
     * con hasta 2 decimales): "2500.00" → "2.500" · "12400000.50" → "12.400.000,5". Para textos
     * que arma el backend (historial, W-23); en el resto de la API el importe viaja crudo.
     */
    public function legible(): string
    {
        $negativo = str_starts_with($this->valor, '-');
        [$enteros, $decimales] = explode('.', ltrim($this->valor, '-'));
        $conPuntos = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $enteros);
        $decimales = rtrim($decimales, '0');
        $texto = $decimales === '' ? $conPuntos : "{$conPuntos},{$decimales}";

        return $negativo && $texto !== '0' ? "-{$texto}" : $texto;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
