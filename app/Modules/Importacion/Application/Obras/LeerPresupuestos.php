<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Importacion\Application\Lectura\RevisorDeFila;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Application\RubrosDelSistema;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;
use App\Modules\Importacion\Domain\Plantilla\Plantillas;
use App\Modules\Obras\Contracts\CargaInicialDeObras;
use App\Modules\Obras\Contracts\PresupuestoInicial;
use App\Shared\CargaInicial\CargaRechazada;

/**
 * Hoja Presupuestos (§5.2, RN-IMP-22): el principal (solo si trae montos u OC) y los adicionales de cada obra, con
 * las reglas de `obras.md`: aprobación opcional, **por rubro o solo total, no mezclado** (RN-OBR-31) y el monto del
 * presupuesto (RN-OBR-36). De una obra que ya está en el sistema no se lee nada (RN-IMP-20).
 */
final readonly class LeerPresupuestos
{
    private const LARGO_DESCRIPCION = 200;

    private const LARGO_OC = 60;

    public function __construct(
        private CargaInicialDeObras $obras,
        private RubrosDelSistema $rubros,
    ) {}

    /**
     * @param  list<FilaLeida>  $filas
     * @param  array<string, ObraDelLote>  $obras
     */
    public function handle(array $filas, array $obras, Informe $informe): void
    {
        foreach ($filas as $fila) {
            $escrita = $fila->texto(C::OBRA) ?? '';
            $clave = $this->normalizado($escrita);
            $obra = $clave === null ? null : ($obras[$clave] ?? null);
            $r = new RevisorDeFila(C::HOJA_PRESUPUESTOS, $fila, $obra->numero ?? $escrita, Informe::ADMINISTRACION);

            if ($obra === null) {
                $r->falta(C::OBRA, $escrita === '' ? T::falta(C::OBRA) : "La obra {$escrita} no está en la hoja Obras.");
                array_push($informe->faltantes, ...$r->faltantes);

                continue;
            }

            if ($obra->existente !== null) {
                continue;
            }

            $presupuesto = $this->presupuesto($r, $obra);
            $obra->faltan($r->faltantes);

            if ($presupuesto !== null && $obra->presupuestos !== []) {
                $this->sumar($obra, $presupuesto);
            }
        }
    }

    private function presupuesto(RevisorDeFila $r, ObraDelLote $obra): ?PresupuestoInicial
    {
        $numero = $this->numero($r);
        $esPrincipal = $numero === $obra->numero;
        $revision = $this->revision($r);
        $descripcion = $r->texto(C::DESCRIPCION, false, self::LARGO_DESCRIPCION);
        $oferta = $r->link(C::OFERTA_ADICIONAL);
        $tipo = $r->lista(C::APROBACION_TIPO, Plantillas::APROBACIONES, 'El documento de aprobación tiene que ser Orden de compra, Orden de trabajo, Contrato o Mail de aprobación.');
        $oc = $r->texto(C::APROBACION_NUMERO, false, self::LARGO_OC);
        $linkAprobacion = $r->link(C::APROBACION_LINK);
        $modo = $r->lista(C::MODO, Plantillas::MODOS, "'Cómo se carga' tiene que ser Por rubro o Solo total.");
        [$montos, $total] = $this->montos($r, is_string($modo) ? $modo : null);
        $montoObra = $this->noNegativo($r, C::MONTO_OBRA, $r->importe(C::MONTO_OBRA));

        if ($numero === null || ! $r->sinFaltantes() || $tipo === false || $modo === false) {
            return null;
        }

        if ($esPrincipal) {
            $principal = $obra->presupuestos[0] ?? null;
            $revision = $principal?->revision;
            $oferta = $principal?->ofertaUrl;
        }

        return new PresupuestoInicial($numero, $revision, $descripcion, $oferta, $tipo, $oc, $linkAprobacion, $modo ?? 'sin_cargar', $montos, $total, $montoObra);
    }

    /**
     * RN-IMP-22: "Por rubro" = las 12 columnas (vacío = 0, no viene); "Solo total" = la columna Total. Mezclado es
     * faltante; montos sin decir cómo se cargan, también.
     *
     * @return array{array<string, numeric-string>, numeric-string|null}
     */
    private function montos(RevisorDeFila $r, ?string $modo): array
    {
        $montos = [];

        foreach ($this->rubros->presupuestables() as $nombre => $clave) {
            $importe = $this->noNegativo($r, $nombre, $r->importe($nombre));

            if ($importe !== null) {
                $montos[$clave] = $importe;
            }
        }

        $total = $this->noNegativo($r, C::TOTAL, $r->importe(C::TOTAL));
        $hayRubros = $montos !== [];

        if (($modo === 'por_rubro' && $total !== null) || ($modo === 'solo_total' && $hayRubros)) {
            $r->falta(C::MODO, T::MEZCLADO);
        } elseif ($modo === 'solo_total' && $total === null) {
            $r->falta(C::TOTAL, T::falta(C::TOTAL));
        } elseif ($modo === null && ($hayRubros || $total !== null)) {
            $r->falta(C::MODO, 'Falta cómo se carga: Por rubro o Solo total.');
        }

        return [$modo === 'por_rubro' ? $montos : [], $modo === 'solo_total' ? $total : null];
    }

    /**
     * @param  numeric-string|null  $importe
     * @return numeric-string|null
     */
    private function noNegativo(RevisorDeFila $r, string $columna, ?string $importe): ?string
    {
        if ($importe !== null && bccomp($importe, '0', 2) < 0) {
            $r->falta($columna, "'{$columna}' no puede ser negativo.");

            return null;
        }

        return $importe;
    }

    /** El principal reemplaza al que armó la hoja Obras (con su revisión y su oferta); un adicional se suma. */
    private function sumar(ObraDelLote $obra, PresupuestoInicial $presupuesto): void
    {
        foreach ($obra->presupuestos as $i => $existente) {
            if ($existente->numero === $presupuesto->numero) {
                if ($i === 0 && $existente->modo === 'sin_cargar' && $existente->aprobacionTipo === null) {
                    $obra->presupuestos[0] = $presupuesto;
                } else {
                    $obra->faltantes[] = new Faltante(
                        C::HOJA_PRESUPUESTOS, null, $obra->numero, C::NUMERO,
                        "El presupuesto {$presupuesto->numero} está repetido en la hoja Presupuestos.", Informe::ADMINISTRACION,
                    );
                }

                return;
            }
        }

        $obra->presupuestos[] = $presupuesto;
    }

    private function numero(RevisorDeFila $r): ?string
    {
        $escrito = $r->texto(C::NUMERO, true);

        return $escrito === null ? null : $this->normalizado($escrito, $r);
    }

    private function revision(RevisorDeFila $r): ?string
    {
        $escrita = $r->texto(C::REVISION);

        try {
            return $escrita === null ? null : $this->obras->revision($escrita);
        } catch (CargaRechazada $e) {
            $r->falta(C::REVISION, $e->getMessage());

            return null;
        }
    }

    private function normalizado(string $escrito, ?RevisorDeFila $r = null): ?string
    {
        try {
            return $escrito === '' ? null : $this->obras->numero($escrito);
        } catch (CargaRechazada $e) {
            $r?->falta(C::NUMERO, $e->getMessage());

            return null;
        }
    }
}
