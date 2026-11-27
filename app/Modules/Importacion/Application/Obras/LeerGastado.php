<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Importacion\Application\Lectura\RevisorDeFila;
use App\Modules\Importacion\Application\Lectura\Sugerencia;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Application\RubrosDelSistema;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lectura\Valores;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;

/**
 * Hojas Gastado y Rubros (§5.3, §5.4, RN-IMP-16, 17, 23): cada fila va a un rubro del sistema **solo** si está escrita
 * igual que su nombre ("INSUMOS", "insumos ") o si la hoja Rubros tiene su equivalencia con Aprobado = Sí; nada se
 * mapea solo (DU-21). Las filas del mismo rubro se suman; un rubro en 0 no genera renglón.
 */
final readonly class LeerGastado
{
    public function __construct(private RubrosDelSistema $rubros) {}

    /**
     * @param  list<FilaLeida>  $gastado
     * @param  list<FilaLeida>  $equivalencias
     */
    public function handle(array $gastado, array $equivalencias, LoteDeObras $lote, Informe $informe): void
    {
        $tabla = $this->equivalencias($equivalencias);

        foreach ($gastado as $fila) {
            $escrita = $fila->texto(C::OBRA) ?? '';
            $obra = $lote->buscar($escrita);
            $r = new RevisorDeFila(C::HOJA_GASTADO, $fila, $obra->numero ?? $escrita, Informe::ADMINISTRACION);

            if ($obra === null) {
                $r->falta(C::OBRA, $escrita === '' ? T::falta(C::OBRA) : "La obra {$escrita} no está en la hoja Obras.");
                array_push($informe->faltantes, ...$r->faltantes);

                continue;
            }

            $obra->tieneGastado = true;
            $escrito = $r->texto(C::RUBRO_ESCRITO, true);
            $importe = $r->importe(C::GASTADO, true);

            if ($importe !== null && bccomp($importe, '0', 2) < 0) {
                $r->falta(C::GASTADO, T::GASTADO_NEGATIVO);
                $importe = null;
            }

            $clave = $escrito === null ? null : $this->rubro($r, $escrito, $tabla, $obra, $informe, $importe ?? '0.00');
            $obra->faltan($r->faltantes);

            if ($clave !== null && $importe !== null) {
                $obra->gastado[$clave] = bcadd($obra->gastado[$clave] ?? '0.00', $importe, 2);
                $obra->origenDelGastado[$clave][] = "{$escrito} ".T::importe($importe);
            }
        }

        foreach ($lote->obras as $obra) {
            $obra->gastado = array_filter($obra->gastado, static fn (string $importe): bool => bccomp($importe, '0', 2) !== 0);
        }
    }

    /**
     * @param  array<string, array{clave: ?string, aprobado: ?bool, aprobo: ?string}>  $tabla
     * @param  numeric-string  $importe
     */
    private function rubro(RevisorDeFila $r, string $escrito, array $tabla, ObraDelLote $obra, Informe $informe, string $importe): ?string
    {
        $directo = $this->rubros->claveDe($escrito);

        if ($directo !== null) {
            return $directo;
        }

        $equivalencia = $tabla[mb_strtolower(trim($escrito))] ?? null;

        if ($equivalencia !== null && $equivalencia['aprobado'] === true && $equivalencia['clave'] !== null && $equivalencia['aprobo'] !== null) {
            $uso = "{$escrito} → {$this->rubros->nombreDe($equivalencia['clave'])}";
            $usadas = $obra->equivalencias[$equivalencia['aprobo']] ?? [];
            $obra->equivalencias[$equivalencia['aprobo']] = in_array($uso, $usadas, true) ? $usadas : [...$usadas, $uso];

            return $equivalencia['clave'];
        }

        $r->falta(C::RUBRO_ESCRITO, $equivalencia !== null && $equivalencia['aprobado'] === false ? T::equivalenciaNo($escrito) : T::rubroSinEquivalencia($escrito));
        $informe->rubroPorRevisar($escrito, $obra->numero, $importe, Sugerencia::para($escrito, $this->rubros->nombres()));

        return null;
    }

    /**
     * La hoja Rubros por forma escrita (sin mayúsculas ni espacios al borde). Un rubro del sistema que no es uno de los
     * 13 deja la equivalencia sin rubro: no se usa (RN-IMP-16).
     *
     * @param  list<FilaLeida>  $filas
     * @return array<string, array{clave: ?string, aprobado: ?bool, aprobo: ?string}>
     */
    private function equivalencias(array $filas): array
    {
        $tabla = [];

        foreach ($filas as $fila) {
            $como = $fila->texto(C::COMO_FIGURA);

            if ($como !== null) {
                $tabla[mb_strtolower($como)] = [
                    'clave' => $this->rubros->claveDe($fila->texto(C::RUBRO_SISTEMA) ?? ''),
                    'aprobado' => Valores::siNo($fila->texto(C::APROBADO)),
                    'aprobo' => $fila->texto(C::APROBO),
                ];
            }
        }

        return $tabla;
    }
}
