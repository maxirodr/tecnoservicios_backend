<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Contracts;

/**
 * La carga inicial vigente de una obra, para no duplicarla (RN-IMP-08) y para el faltante "La obra ya tiene su carga
 * inicial de gastado (aprobó Carla el 30/11/2026)…". `porClave`: rubro => total (gastado) o materialId => cantidad
 * (retirado), para saber si es la misma que se quiere cargar.
 */
final readonly class CargaInicialVigente
{
    /**
     * @param  array<string, numeric-string>  $porClave
     */
    public function __construct(
        public int $gastoId,
        public string $fechaCorte,
        public string $cargo,
        public string $cargadaEl,
        public array $porClave,
    ) {}
}
