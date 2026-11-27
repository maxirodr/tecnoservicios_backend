<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

/**
 * Lo que el comando muestra y su código de salida (§11.1): 0 si terminó (aunque haya faltantes), 1 si no empezó o
 * terminó "con errores". Solo conteos, huella corta y ruta (RN-IMP-13).
 */
final readonly class Salida
{
    /**
     * @param  list<string>  $lineas
     */
    public function __construct(
        public bool $exito,
        public array $lineas,
        public ?string $huellaCorta = null,
    ) {}

    public static function noEmpezo(string $motivo): self
    {
        return new self(false, [$motivo]);
    }
}
