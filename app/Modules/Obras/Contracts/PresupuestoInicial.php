<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/**
 * Un presupuesto de la carga inicial (`importacion.md` §5.2): N.º y revisión normalizados, links de la oferta y de
 * la aprobación, y lo presupuestado por rubro (clave => importe) o solo el total (RN-OBR-31). Importes string decimal.
 */
final readonly class PresupuestoInicial
{
    /**
     * @param  'oc'|'ot'|'contrato'|'mail'|null  $aprobacionTipo
     * @param  'sin_cargar'|'por_rubro'|'solo_total'  $modo
     * @param  array<string, numeric-string>  $montos  clave del rubro presupuestable => importe (los vacíos no vienen)
     * @param  numeric-string|null  $total
     * @param  numeric-string|null  $montoObra
     */
    public function __construct(
        public string $numero,
        public ?string $revision,
        public ?string $descripcion,
        public ?string $ofertaUrl,
        public ?string $aprobacionTipo,
        public ?string $aprobacionNumero,
        public ?string $aprobacionUrl,
        public string $modo,
        public array $montos,
        public ?string $total,
        public ?string $montoObra,
    ) {}
}
