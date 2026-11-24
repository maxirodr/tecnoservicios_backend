<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\NumeroComprobante;
use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Modules\Proveedores\Contracts\ProveedorId;

/**
 * Cabecera de cada carga (gastos.md §4.1, §4.3): número (RN-GAS-03, 07, 24), fecha (RN-GAS-04, 27),
 * proveedor (RN-GAS-05, 06, 20, 24, 25) y "rendido por" (RN-GAS-18). Textos de la spec, al pie de la letra.
 */
final readonly class ReglasDeCabecera
{
    public const GENERICO_EN_FACTURA = 'El proveedor genérico es solo para gastos sin documento.';

    public const FECHA_FUTURA = 'La fecha no puede ser posterior a hoy.';

    public function __construct(private ProveedoresConsulta $proveedores) {}

    /**
     * @return array{numero: string|null, fecha: string|null, proveedorId: int|null, rendidoPor: string|null, desde: string|null, hasta: string|null}
     */
    public function aplicar(DatosGasto $datos, Errores $errores): array
    {
        [$desde, $hasta] = $this->periodo($datos, $errores);

        return [
            'numero' => $this->numero($datos, $errores),
            'fecha' => $datos->origen === Origen::MontoPeriodo ? $hasta : $this->fecha($datos->fecha, $errores),
            'proveedorId' => $this->proveedor($datos, $errores),
            'rendidoPor' => $this->rendidoPor($datos, $errores),
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    private function numero(DatosGasto $datos, Errores $errores): ?string
    {
        $faltante = match ($datos->origen) {
            Origen::Factura => 'Poné el número de la factura.',
            Origen::RetiroRemito => 'Poné el número de remito.',
            default => null,
        };

        if ($faltante === null) {
            return null;
        }

        if ($datos->numero === null || trim($datos->numero) === '') {
            $errores->campo('numero', $faltante);

            return null;
        }

        return NumeroComprobante::normalizar($datos->numero);
    }

    private function fecha(?string $fecha, Errores $errores): ?string
    {
        if ($fecha === null) {
            $errores->campo('fecha', 'Poné la fecha.');

            return null;
        }

        if ($fecha > FechaEmpresa::hoy()) {
            $errores->campo('fecha', self::FECHA_FUTURA);
        }

        return $fecha;
    }

    /**
     * RN-GAS-27: dos fechas, hasta ≥ desde, desde ≤ hoy; el "hasta" puede ser futuro (los fijos del mes).
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function periodo(DatosGasto $datos, Errores $errores): array
    {
        if ($datos->origen !== Origen::MontoPeriodo) {
            return [null, null];
        }

        if ($datos->periodoDesde === null || $datos->periodoHasta === null) {
            $errores->campo('periodo_desde', 'Poné desde y hasta.');

            return [$datos->periodoDesde, $datos->periodoHasta];
        }

        if ($datos->periodoHasta < $datos->periodoDesde) {
            $errores->campo('periodo_hasta', "El 'hasta' no puede ser anterior al 'desde'.");
        }

        if ($datos->periodoDesde > FechaEmpresa::hoy()) {
            $errores->campo('periodo_desde', 'El período no puede empezar después de hoy.');
        }

        return [$datos->periodoDesde, $datos->periodoHasta];
    }

    /** Factura y retiro: uno de la base, no el genérico. W-35 y devolución: el genérico si no viene. El resto: el genérico. */
    private function proveedor(DatosGasto $datos, Errores $errores): ?int
    {
        $deLaBase = $datos->origen === Origen::Factura || $datos->origen === Origen::RetiroRemito;
        $opcional = $datos->origen->esSinDocumentoDeW35() || $datos->origen === Origen::Devolucion;

        if (! $deLaBase && (! $opcional || $datos->proveedorId === null)) {
            return $this->proveedores->generico()->valor;
        }

        if ($datos->proveedorId === null || ! $this->proveedores->existe(new ProveedorId($datos->proveedorId))) {
            $errores->campo('proveedor_id', 'Elegí el proveedor.');

            return null;
        }

        if ($deLaBase && $this->proveedores->esGenerico(new ProveedorId($datos->proveedorId))) {
            $errores->campo('proveedor_id', $datos->origen === Origen::Factura ? self::GENERICO_EN_FACTURA : 'Elegí el proveedor.');
        }

        return $datos->proveedorId;
    }

    /** Obligatorio en "Gastos de obra rendidos"; opcional en combustible (el chofer); en el resto no va. */
    private function rendidoPor(DatosGasto $datos, Errores $errores): ?string
    {
        if ($datos->origen !== Origen::SinComprobante && $datos->origen !== Origen::Combustible) {
            return null;
        }

        if ($datos->origen === Origen::SinComprobante && $datos->rendidoPor === null) {
            $errores->campo('rendido_por', 'Elegí o escribí quién rindió.');
        }

        return $datos->rendidoPor;
    }
}
