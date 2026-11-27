<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Importacion\Application\Lectura\RevisorDeFila;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Domain\Informe\Aviso;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lectura\Valores;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;
use App\Modules\Obras\Contracts\CargaInicialDeObras;
use App\Shared\CargaInicial\CargaRechazada;

/**
 * Hoja Materiales (§5.5, RN-IMP-28, 29, 31, resuelta DU-I06): la lista de W-48 de cada obra con lo retirado hasta el
 * corte. Nombre no repetido en la obra, unidad, cantidad ≥ 0 con hasta 3 decimales y el presupuesto de **esa** obra
 * si tiene más de uno. Retirado mayor que lo presupuestado entra con aviso.
 */
final readonly class LeerMateriales
{
    private const LARGO_NOMBRE = 150;

    private const LARGO_UNIDAD = 20;

    public function __construct(private CargaInicialDeObras $obras) {}

    /**
     * @param  list<FilaLeida>  $filas
     */
    public function handle(array $filas, LoteDeObras $lote, Informe $informe): void
    {
        $nombres = [];

        foreach ($filas as $fila) {
            $escrita = $fila->texto(C::OBRA) ?? '';
            $obra = $lote->buscar($escrita);
            $r = new RevisorDeFila(C::HOJA_MATERIALES, $fila, $obra->numero ?? $escrita, Informe::ADMINISTRACION);

            if ($obra === null) {
                $r->falta(C::OBRA, $escrita === '' ? T::falta(C::OBRA) : "La obra {$escrita} no está en la hoja Obras.");
                array_push($informe->faltantes, ...$r->faltantes);

                continue;
            }

            $material = $this->material($r, $obra);
            $clave = Valores::clave($material->nombre ?? '');

            if ($material !== null && isset($nombres[$obra->numero][$clave])) {
                $r->falta(C::MATERIAL, "{$material->nombre} está repetido en la hoja Materiales (filas {$nombres[$obra->numero][$clave]} y {$fila->numero}).");
            } elseif ($material !== null) {
                $nombres[$obra->numero][$clave] = $fila->numero;
                $obra->materiales[] = $material;
                $this->avisarExceso($obra, $material);
            }

            $obra->faltan($r->faltantes);
        }
    }

    private function material(RevisorDeFila $r, ObraDelLote $obra): ?MaterialDelLote
    {
        $nombre = $r->texto(C::MATERIAL, true, self::LARGO_NOMBRE);
        $unidad = $r->texto(C::UNIDAD, true, self::LARGO_UNIDAD);
        $cantidad = $this->noNegativa($r, C::CANTIDAD, $r->cantidad(C::CANTIDAD, true));
        $precio = $r->importe(C::PRECIO);
        $retirado = $this->noNegativa($r, C::RETIRADO, $r->cantidad(C::RETIRADO));
        $presupuesto = $this->presupuesto($r, $obra);

        if ($precio !== null && bccomp($precio, '0', 2) < 0) {
            $r->falta(C::PRECIO, "'".C::PRECIO."' no puede ser negativo.");
        }

        if (! $r->sinFaltantes() || $nombre === null || $unidad === null || $cantidad === null) {
            return null;
        }

        return new MaterialDelLote($r->fila->numero, $nombre, $unidad, $cantidad, $precio, $presupuesto, $retirado);
    }

    /** El N.º de un presupuesto de esa obra; obligatorio si la obra tiene más de uno (rendicion.md §4.2). */
    private function presupuesto(RevisorDeFila $r, ObraDelLote $obra): ?string
    {
        $escrito = $r->texto(C::PRESUPUESTO);
        $deLaObra = $obra->existente === null
            ? array_map(static fn ($p): string => $p->numero, $obra->presupuestos)
            : array_keys($this->obras->presupuestos($obra->existente));

        if ($escrito === null) {
            if (count($deLaObra) > 1) {
                $r->falta(C::PRESUPUESTO, T::falta(C::PRESUPUESTO));
            }

            return null;
        }

        try {
            $numero = $this->obras->numero($escrito);
        } catch (CargaRechazada $e) {
            $r->falta(C::PRESUPUESTO, $e->getMessage());

            return null;
        }

        if (! in_array($numero, $deLaObra, true)) {
            $r->falta(C::PRESUPUESTO, "El presupuesto {$numero} no es de esta obra.");

            return null;
        }

        return $numero;
    }

    /**
     * @param  numeric-string|null  $cantidad
     * @return numeric-string|null
     */
    private function noNegativa(RevisorDeFila $r, string $columna, ?string $cantidad): ?string
    {
        if ($cantidad !== null && bccomp($cantidad, '0', 3) < 0) {
            $r->falta($columna, "'{$columna}' no puede ser negativa.");

            return null;
        }

        return $cantidad;
    }

    /** RN-IMP-31: "Cemento x 50 kg: retirado 160 de 150 presupuestadas (resta llevar −10)." */
    private function avisarExceso(ObraDelLote $obra, MaterialDelLote $m): void
    {
        if ($m->retirado === null || bccomp($m->retirado, $m->cantidad, 3) <= 0) {
            return;
        }

        $resta = bcsub($m->cantidad, $m->retirado, 3);
        $obra->avisos[] = new Aviso(C::HOJA_MATERIALES, $m->fila, $obra->numero, "{$m->nombre}: retirado ".T::cantidad($m->retirado)
            .' de '.T::cantidad($m->cantidad).' presupuestadas (resta llevar '.T::cantidad($resta).').');
    }
}
