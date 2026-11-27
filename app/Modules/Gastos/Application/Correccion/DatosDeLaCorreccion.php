<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Application\Carga\DatosGasto;
use App\Modules\Gastos\Application\Carga\DatosRenglon;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\NumeroComprobante;

/**
 * Lo guardado + lo que cambió = el gasto a validar con las mismas reglas de la carga (RN-GAS-37). El aviso
 * de remito repetido y el de período superpuesto vuelven a salir solo si cambió lo que los dispara.
 */
final readonly class DatosDeLaCorreccion
{
    public function __construct(private Rubros $rubros) {}

    public function armar(Gasto $gasto, CambiosGasto $cambios): DatosGasto
    {
        $renglones = $cambios->renglones ?? $this->guardados($gasto);
        $numero = $this->texto($cambios, 'numero', $gasto->numero);
        $proveedorId = $cambios->trae('proveedor_id') ? $this->entero($cambios->cabecera['proveedor_id']) : $gasto->proveedor_id;
        $desde = $this->texto($cambios, 'periodo_desde', $gasto->periodo_desde?->toDateString());
        $hasta = $this->texto($cambios, 'periodo_hasta', $gasto->periodo_hasta?->toDateString());

        $mismoRemito = ($numero === null ? null : NumeroComprobante::normalizar($numero)) === $gasto->numero
            && $proveedorId === $gasto->proveedor_id;
        $mismoPeriodo = $desde === $gasto->periodo_desde?->toDateString()
            && $hasta === $gasto->periodo_hasta?->toDateString()
            && ($gasto->origen === Origen::ParteHoras ? $this->sinObrasNuevas($gasto, $renglones) : $this->mismaObraYRubro($gasto, $renglones));

        return new DatosGasto(
            $gasto->origen,
            $numero,
            $this->texto($cambios, 'fecha', $gasto->fecha->toDateString()),
            $proveedorId,
            $this->texto($cambios, 'rendido_por', $gasto->rendido_por),
            $desde,
            $hasta,
            $cambios->adjuntos ?? [],
            $renglones,
            $cambios->confirmarRemitoRepetido || $mismoRemito,
            $cambios->confirmarSuperposicion || $mismoPeriodo,
            $this->texto($cambios, 'concepto', $gasto->origen === Origen::ParteHoras ? $gasto->renglones->first()?->concepto : null),
        );
    }

    /**
     * @return list<DatosRenglon>
     */
    private function guardados(Gasto $gasto): array
    {
        return array_values($gasto->renglones->map(fn (GastoRenglon $r, int $i): DatosRenglon => new DatosRenglon(
            $i,
            $r->id,
            $r->fecha->toDateString(),
            $r->concepto,
            $r->cantidad,
            $r->precio_unitario,
            $r->obra_id,
            $this->rubros->porId($r->rubro_id)?->clave,
            $r->material_id,
            null,
            $r->detalle,
            $r->empleado,
        ))->all());
    }

    /**
     * @param  list<DatosRenglon>  $renglones
     */
    private function mismaObraYRubro(Gasto $gasto, array $renglones): bool
    {
        $guardado = $gasto->renglones->first();
        $nuevo = $renglones[0] ?? null;

        return count($renglones) === 1 && $guardado !== null && $nuevo !== null
            && $nuevo->obraId === $guardado->obra_id
            && $nuevo->rubroClave === $this->rubros->porId($guardado->rubro_id)?->clave;
    }

    /**
     * Parte de horas (RN-GAS-55): vuelve a avisar la superposición solo si se sumó una obra.
     *
     * @param  list<DatosRenglon>  $renglones
     */
    private function sinObrasNuevas(Gasto $gasto, array $renglones): bool
    {
        $guardadas = $gasto->obraIds();

        foreach ($renglones as $renglon) {
            if ($renglon->obraId !== null && ! in_array($renglon->obraId, $guardadas, true)) {
                return false;
            }
        }

        return true;
    }

    private function texto(CambiosGasto $cambios, string $campo, ?string $guardado): ?string
    {
        if (! $cambios->trae($campo)) {
            return $guardado;
        }

        $valor = $cambios->cabecera[$campo];

        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }

    private function entero(string|int|null $valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }
}
