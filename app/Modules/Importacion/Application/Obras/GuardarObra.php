<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Gastos\Contracts\CargaInicialDeGastos;
use App\Modules\Gastos\Contracts\RenglonInicial;
use App\Modules\Importacion\Application\Corridas\FilaDeCorrida;
use App\Modules\Importacion\Application\Corridas\Rechazada;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Domain\Informe\Aviso;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;
use App\Modules\Importacion\Domain\Resultado;
use App\Modules\Obras\Contracts\CargaInicialDeObras;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Contracts\MaterialInicial;
use App\Shared\CargaInicial\CargaRechazada;

/**
 * Guarda una obra del lote por los contratos de cada módulo (RN-IMP-09): la obra (si no está), su lista de
 * materiales, su gastado y su retirado como cargas iniciales (RN-IMP-21…29). Lo que ya estaba no se toca ni se
 * duplica (RN-IMP-08). Corre dentro de la transacción de la obra: si algo se rechaza, `Rechazada` la deshace entera.
 */
final readonly class GuardarObra
{
    public const RUBRO_RETIRADO = 'materiales';

    public function __construct(
        private CargaInicialDeObras $obras,
        private MaterialesDeObra $materiales,
        private CargaInicialDeGastos $gastos,
    ) {}

    /**
     * @return list<FilaDeCorrida>
     *
     * @throws Rechazada
     */
    public function handle(int $actorId, ObraDelLote $obra): array
    {
        $filas = [$this->obra($actorId, $obra)];
        $obraId = (int) $filas[0]->sujetoId;
        $faltantes = [];
        $ids = $this->materiales($actorId, $obraId, $obra, $faltantes);

        if ($obra->gastado !== []) {
            $filas[] = $this->gastado($actorId, $obraId, $obra, $faltantes);
        }

        $retirado = $this->retirado($obra, $ids);

        if ($retirado !== []) {
            $filas[] = $this->retiro($actorId, $obraId, $obra, $retirado, $faltantes);
        }

        if ($faltantes !== []) {
            throw new Rechazada($faltantes);
        }

        return $filas;
    }

    private function obra(int $actorId, ObraDelLote $obra): FilaDeCorrida
    {
        if ($obra->existente !== null) {
            return new FilaDeCorrida(C::HOJA_OBRAS, $obra->numero, Resultado::YaEstaba, 'obra', $obra->existente);
        }

        $datos = $obra->datos() ?? throw new Rechazada([$this->faltante($obra, C::HOJA_OBRAS, $obra->fila, C::NUMERO, T::ERROR_INESPERADO_OBRA)]);

        try {
            return new FilaDeCorrida(C::HOJA_OBRAS, $obra->numero, Resultado::Importada, 'obra', $this->obras->crear($actorId, $datos));
        } catch (CargaRechazada $e) {
            throw new Rechazada(array_map(fn (string $m): Faltante => $this->faltante($obra, C::HOJA_OBRAS, $obra->fila, C::NUMERO, $m), $e->motivos));
        }
    }

    /**
     * RN-IMP-28: por el alta de W-48; uno que ya está por su nombre no se duplica ni se cambia (aviso si difiere).
     *
     * @param  list<Faltante>  $faltantes
     * @return array<int, int> fila de la hoja => id del material
     */
    private function materiales(int $actorId, int $obraId, ObraDelLote $obra, array &$faltantes): array
    {
        $presupuestos = $obra->materiales === [] ? [] : $this->obras->presupuestos($obraId);
        $ids = [];

        foreach ($obra->materiales as $m) {
            try {
                $agregado = $this->materiales->agregarALaLista($actorId, $obraId, new MaterialInicial(
                    $m->nombre, $m->unidad, $m->cantidad, $m->precio, $m->presupuesto === null ? null : ($presupuestos[$m->presupuesto] ?? null),
                ));
            } catch (CargaRechazada $e) {
                array_push($faltantes, ...array_map(fn (string $msg): Faltante => $this->faltante($obra, C::HOJA_MATERIALES, $m->fila, C::MATERIAL, $msg), $e->motivos));

                continue;
            }

            $ids[$m->fila] = $agregado->id;

            if (! $agregado->nuevo && $agregado->cantidadExistente !== $m->cantidad) {
                $existente = $agregado->cantidadExistente === null ? '—' : T::cantidad($agregado->cantidadExistente);
                $obra->avisos[] = new Aviso(C::HOJA_MATERIALES, $m->fila, $obra->numero, "{$m->nombre} ya está en la lista con {$existente}: no se cambia.");
            }
        }

        return $ids;
    }

    /**
     * RN-IMP-23, 25, 27: una carga inicial vigente por obra; la misma que ya está es "Ya estaba"; otra distinta, o
     * renglones de otro origen hasta el corte (doble conteo), son faltantes.
     *
     * @param  list<Faltante>  $faltantes
     */
    private function gastado(int $actorId, int $obraId, ObraDelLote $obra, array &$faltantes): FilaDeCorrida
    {
        $clave = "gastado:{$obra->numero}";
        $corte = (string) $obra->corte;
        $vigente = $this->gastos->vigente($obraId, 'gastado');

        if ($vigente !== null) {
            if ($vigente->fechaCorte === $corte && self::iguales($vigente->porClave, $obra->gastado)) {
                return new FilaDeCorrida(C::HOJA_GASTADO, $clave, Resultado::YaEstaba, 'gasto', $vigente->gastoId);
            }

            $faltantes[] = $this->faltante($obra, C::HOJA_GASTADO, null, C::GASTADO, T::cargaVigente('gastado', $vigente->cargo, T::fecha($vigente->cargadaEl)));

            return new FilaDeCorrida(C::HOJA_GASTADO, $clave, Resultado::Faltante);
        }

        $previos = $this->gastos->renglonesHastaFecha($obraId, $corte);

        if ($previos > 0) {
            $faltantes[] = $this->faltante($obra, C::HOJA_GASTADO, null, C::GASTADO, T::dobleConteo($previos, T::fecha($corte)));

            return new FilaDeCorrida(C::HOJA_GASTADO, $clave, Resultado::Faltante);
        }

        $concepto = 'Gastado hasta el '.T::fecha($corte).' según su rendición';
        $renglones = [];

        foreach ($obra->gastado as $rubro => $importe) {
            $renglones[] = new RenglonInicial($rubro, $concepto, '1', $importe);
        }

        return $this->cargar($actorId, $obraId, $obra, 'gastado', $renglones, self::historial($obra), $clave, C::HOJA_GASTADO, $faltantes);
    }

    /**
     * RN-IMP-29 (DU-R09): un renglón por material con retirado > 0, precio 0, en MATERIALES. No suma plata.
     *
     * @param  array<int, numeric-string>  $retirado  materialId => cantidad
     * @param  list<Faltante>  $faltantes
     */
    private function retiro(int $actorId, int $obraId, ObraDelLote $obra, array $retirado, array &$faltantes): FilaDeCorrida
    {
        $clave = "retirado:{$obra->numero}";
        $corte = (string) $obra->corte;
        $vigente = $this->gastos->vigente($obraId, 'retirado');
        $porMaterial = [];

        foreach ($retirado as $id => $cantidad) {
            $porMaterial[(string) $id] = $cantidad;
        }

        if ($vigente !== null) {
            if ($vigente->fechaCorte === $corte && self::iguales($vigente->porClave, $porMaterial)) {
                return new FilaDeCorrida(C::HOJA_MATERIALES, $clave, Resultado::YaEstaba, 'gasto', $vigente->gastoId);
            }

            $faltantes[] = $this->faltante($obra, C::HOJA_MATERIALES, null, C::RETIRADO, T::cargaVigente('retirado', $vigente->cargo, T::fecha($vigente->cargadaEl)));

            return new FilaDeCorrida(C::HOJA_MATERIALES, $clave, Resultado::Faltante);
        }

        $concepto = 'Retirado hasta el '.T::fecha($corte).' (el importe está en el gastado acumulado)';
        $renglones = [];

        foreach ($retirado as $id => $cantidad) {
            $renglones[] = new RenglonInicial(self::RUBRO_RETIRADO, $concepto, $cantidad, '0', $id);
        }

        return $this->cargar($actorId, $obraId, $obra, 'retirado', $renglones, [], $clave, C::HOJA_MATERIALES, $faltantes);
    }

    /**
     * @param  'gastado'|'retirado'  $tipo
     * @param  list<RenglonInicial>  $renglones  al menos uno
     * @param  list<string>  $historial
     * @param  list<Faltante>  $faltantes
     */
    private function cargar(int $actorId, int $obraId, ObraDelLote $obra, string $tipo, array $renglones, array $historial, string $clave, string $hoja, array &$faltantes): FilaDeCorrida
    {
        try {
            $gastoId = $this->gastos->cargar($actorId, $obraId, (string) $obra->corte, $tipo, $renglones, $historial);
        } catch (CargaRechazada $e) {
            array_push($faltantes, ...array_map(fn (string $m): Faltante => $this->faltante($obra, $hoja, null, $tipo === 'gastado' ? C::GASTADO : C::RETIRADO, $m), $e->motivos));

            return new FilaDeCorrida($hoja, $clave, Resultado::Faltante);
        }

        return new FilaDeCorrida($hoja, $clave, Resultado::Importada, 'gasto', $gastoId);
    }

    /**
     * @param  array<int, int>  $ids  fila => material
     * @return array<int, numeric-string> material => retirado
     */
    private function retirado(ObraDelLote $obra, array $ids): array
    {
        $retirado = [];

        foreach ($obra->materiales as $m) {
            if ($m->conRetirado() && isset($ids[$m->fila]) && $m->retirado !== null) {
                $retirado[$ids[$m->fila]] = $m->retirado;
            }
        }

        return $retirado;
    }

    /**
     * RN-IMP-17: "Equivalencias aprobadas por Carla: INSUMO → INSUMOS, LOGÍSTICA → LOGISTICA."
     *
     * @return list<string>
     */
    private static function historial(ObraDelLote $obra): array
    {
        $lineas = [];

        foreach ($obra->equivalencias as $aprobo => $usadas) {
            $lineas[] = "Equivalencias aprobadas por {$aprobo}: ".implode(', ', $usadas).'.';
        }

        return $lineas;
    }

    /**
     * @param  array<array-key, string>  $a
     * @param  array<array-key, string>  $b
     */
    private static function iguales(array $a, array $b): bool
    {
        ksort($a);
        ksort($b);

        return $a === $b;
    }

    private function faltante(ObraDelLote $obra, string $hoja, ?int $fila, string $columna, string $que): Faltante
    {
        return new Faltante($hoja, $fila, $obra->numero, $columna, $que, Informe::ADMINISTRACION);
    }
}
