<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Shared\Domain\Importe;

/**
 * Reglas de cada renglón según la carga (gastos.md §4.2, §4.3, RN-GAS-09, 10, 13, 14, 19, 21…27): obra,
 * signo de la cantidad, precio, total (lo calcula el backend), fecha efectiva (resuelta DU-G01) y, con
 * `RubrosDeLaCarga` y `MaterialDelRenglon`, el rubro y el material.
 */
final readonly class ReglasDeRenglones
{
    public const MAXIMO = 200;

    public const DE_LA_RENDICION = 'Todos los renglones van a la obra de la rendición.';

    public const OBRA = "Elegí la obra o '(sin obra)'.";

    public function __construct(
        private ObrasConsulta $obras,
        private RubrosDeLaCarga $rubros,
        private MaterialDelRenglon $materiales,
    ) {}

    /**
     * @return list<RenglonAGuardar>
     */
    public function aplicar(DatosGasto $datos, ?string $fechaCabecera, Errores $errores): array
    {
        $cantidad = count($datos->renglones);

        if ($cantidad === 0) {
            $errores->campo('renglones', 'Cargá al menos un renglón.');
        } elseif ($cantidad > self::MAXIMO) {
            $errores->campo('renglones', 'Hasta 200 renglones por comprobante: dividilo en dos cargas.');
        } elseif ($datos->origen->tieneUnSoloRenglon() && $cantidad > 1) {
            $errores->campo('renglones', 'Esta carga lleva un solo renglón.');
        }

        $obraDeLaRendicion = $datos->renglones[0]->obraId ?? null;
        $conceptoDelParte = $datos->origen === Origen::ParteHoras ? RenglonDelParte::concepto($datos) : null;
        $existentes = [];
        $resueltos = [];

        foreach ($datos->renglones as $renglon) {
            $this->obra($datos->origen, $renglon, $obraDeLaRendicion, $existentes, $errores);
            $resueltos[] = $this->resolver($datos->origen, $renglon, $fechaCabecera, $errores, $conceptoDelParte);
        }

        return $resueltos;
    }

    /**
     * @param  array<int, bool>  $existentes
     */
    private function obra(Origen $origen, DatosRenglon $renglon, ?int $obraDeLaRendicion, array &$existentes, Errores $errores): void
    {
        $obraId = $renglon->obraId;

        if ($origen->seCargaDesdeLaRendicion() && ($obraId === null || $obraId !== $obraDeLaRendicion)) {
            $errores->renglon($renglon->posicion, 'obra_id', self::DE_LA_RENDICION);

            return;
        }

        if ($origen === Origen::ConsumoStock && $obraId === null) {
            $errores->renglon($renglon->posicion, 'obra_id', 'El consumo de stock va a una obra.');

            return;
        }

        if ($obraId !== null && ! ($existentes[$obraId] ??= $this->obras->existe($obraId))) {
            $errores->renglon($renglon->posicion, 'obra_id', self::OBRA);
        }
    }

    /** `$conceptoDelParte`: en el parte de horas, el concepto de la cabecera va en cada renglón (RN-GAS-52). */
    private function resolver(Origen $origen, DatosRenglon $renglon, ?string $fechaCabecera, Errores $errores, ?string $conceptoDelParte = null): RenglonAGuardar
    {
        $porImporte = $origen->tieneUnSoloRenglon();
        $cantidad = $porImporte ? '1.000' : bcadd($renglon->cantidad, '0', TotalDeRenglon::ESCALA_CANTIDAD);
        $precio = bcadd($renglon->precioUnitario, '0', TotalDeRenglon::ESCALA_IMPORTE);

        $this->signo($origen, $renglon->posicion, $cantidad, $errores);
        $esParte = $origen === Origen::ParteHoras;

        if ($conceptoDelParte === null && $renglon->concepto === '') {
            $errores->renglon($renglon->posicion, 'concepto', 'Escribí el concepto.');
        }
        $this->precio($origen, $renglon->posicion, $precio, $errores);

        $total = TotalDeRenglon::calcular($cantidad, $precio);
        [$materialId, $materialNuevo] = $this->materiales->resolver($origen, $renglon, $errores);

        if (Importe::desde(ltrim($total, '-'))->superaElMaximo()) {
            $errores->renglon($renglon->posicion, 'precio_unitario', 'El total del renglón es demasiado grande.');
        }

        return new RenglonAGuardar(
            $renglon->posicion,
            $renglon->id,
            $this->fecha($origen, $renglon, $fechaCabecera, $errores),
            $conceptoDelParte ?? $renglon->concepto,
            $cantidad,
            $precio,
            $total,
            $renglon->obraId,
            $this->rubros->rubroDe($origen, $renglon, $errores),
            $materialId,
            $materialNuevo,
            $renglon->detalle,
            $esParte ? RenglonDelParte::empleado($renglon, $errores) : null,
        );
    }

    /**
     * @param  numeric-string  $cantidad
     */
    private function signo(Origen $origen, int $posicion, string $cantidad, Errores $errores): void
    {
        $signo = bccomp($cantidad, '0', TotalDeRenglon::ESCALA_CANTIDAD);

        if ($origen === Origen::ParteHoras) {
            RenglonDelParte::horas($posicion, $cantidad, $errores);

            return;
        }

        match (true) {
            $signo === 0 => $errores->renglon($posicion, 'cantidad', 'La cantidad no puede ser 0.'),
            $origen === Origen::Devolucion && $signo > 0 => $errores->renglon($posicion, 'cantidad', 'En una devolución la cantidad va en negativo.'),
            $origen !== Origen::Devolucion && $signo < 0 => $errores->renglon($posicion, 'cantidad', 'La cantidad va en negativo solo en una devolución.'),
            default => null,
        };
    }

    /**
     * @param  numeric-string  $precio
     */
    private function precio(Origen $origen, int $posicion, string $precio, Errores $errores): void
    {
        $signo = bccomp($precio, '0', TotalDeRenglon::ESCALA_IMPORTE);

        match (true) {
            $signo < 0 => $errores->renglon($posicion, 'precio_unitario', 'El precio no puede ser negativo.'),
            $origen === Origen::Amortizacion && $signo === 0 => $errores->renglon($posicion, 'precio_unitario', 'Poné el importe.'),
            $origen === Origen::MontoPeriodo && $signo === 0 => $errores->renglon($posicion, 'precio_unitario', 'Poné el importe del período.'),
            $origen === Origen::ParteHoras && $signo === 0 => $errores->renglon($posicion, 'precio_unitario', RenglonDelParte::SIN_VALOR_HORA),
            default => null,
        };
    }

    /** Factura: la de la factura. W-35: la del ticket (≤ hoy). Período: el "hasta". El resto: la de la cabecera. */
    private function fecha(Origen $origen, DatosRenglon $renglon, ?string $fechaCabecera, Errores $errores): string
    {
        if (! $origen->esSinDocumentoDeW35() || $renglon->fecha === null) {
            return $fechaCabecera ?? '';
        }

        if ($renglon->fecha > FechaEmpresa::hoy()) {
            $errores->renglon($renglon->posicion, 'fecha', ReglasDeCabecera::FECHA_FUTURA);
        }

        return $renglon->fecha;
    }
}
