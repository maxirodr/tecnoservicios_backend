<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Domain;

/**
 * CUIT tolerante (RN-PRV-06): se acepta con o sin guiones o espacios, se guardan los 11 dígitos y se
 * muestra `30-00000001-5`. Solo existe si tiene 11 dígitos y el dígito verificador da (módulo 11).
 */
final readonly class Cuit
{
    private const DIGITOS = 11;

    private const PESOS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    private const MODULO = 11;

    private function __construct(public string $digitos) {}

    /** null si no es un CUIT válido. */
    public static function desde(string $texto): ?self
    {
        $limpio = (string) preg_replace('/[\s\-]+/', '', $texto);

        if (preg_match('/^\d{'.self::DIGITOS.'}$/', $limpio) !== 1 || ! self::verificadorValido($limpio)) {
            return null;
        }

        return new self($limpio);
    }

    /** Para mostrar uno ya guardado (11 dígitos). */
    public static function formatear(string $digitos): string
    {
        return substr($digitos, 0, 2).'-'.substr($digitos, 2, 8).'-'.substr($digitos, 10);
    }

    public function formateado(): string
    {
        return self::formatear($this->digitos);
    }

    private static function verificadorValido(string $digitos): bool
    {
        $suma = 0;
        foreach (self::PESOS as $posicion => $peso) {
            $suma += (int) $digitos[$posicion] * $peso;
        }

        $verificador = self::MODULO - ($suma % self::MODULO);
        $verificador = $verificador === self::MODULO ? 0 : $verificador;

        // Resto 1 da 10: ese número no tiene CUIT válido.
        return $verificador < 10 && $verificador === (int) $digitos[self::DIGITOS - 1];
    }
}
