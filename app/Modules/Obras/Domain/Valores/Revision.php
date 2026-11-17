<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Valores;

/**
 * Revisión de un presupuesto (RN-OBR-14), tolerante: `rev01`, `Rev 1`, `REV-1`, `R1` o `1` → `REV01`.
 * La revisión cero (`REV00`, `REV0`) tiene su propio mensaje (PU-122): el formato está bien, el número no.
 */
final readonly class Revision
{
    public const MENSAJE = 'Escribí la revisión como REV01.';

    public const MENSAJE_CERO = 'Las revisiones empiezan en REV01.';

    private const PATRON = '/^(?:R(?:EV)?)?[\s\-_.]*0*(\d{1,2})$/';

    private const DIGITOS = 2;

    private function __construct(public string $valor) {}

    /**
     * @throws ValorInvalido
     */
    public static function desde(string $escrito): self
    {
        $limpio = mb_strtoupper(trim($escrito));

        if (preg_match(self::PATRON, $limpio, $partes) !== 1) {
            throw new ValorInvalido(self::MENSAJE);
        }

        if ((int) $partes[1] === 0) {
            throw new ValorInvalido(self::MENSAJE_CERO);
        }

        return new self('REV'.str_pad($partes[1], self::DIGITOS, '0', STR_PAD_LEFT));
    }

    public function equals(self $otra): bool
    {
        return $this->valor === $otra->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
