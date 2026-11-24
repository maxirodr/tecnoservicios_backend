<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Application\Carga\GastoAGuardar;
use App\Modules\Gastos\Application\Carga\GuardarRenglones;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\Legible;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Shared\Actividad\RegistroActividad;

/**
 * Aplica la corrección y deja cada cambio en `actividades`, con antes → después en palabras del cliente
 * (RN-GAS-42): `editado` (cabecera), `renglon_cambiado`, `renglon_agregado`, `renglon_quitado`. Los de
 * renglón llevan `_obras` (las de antes y después) para que quien ve "solo sus obras" no lea los ajenos.
 */
final readonly class EscribirCorreccion
{
    public const EDITADO = 'editado';

    public const RENGLON_CAMBIADO = 'renglon_cambiado';

    public const RENGLON_AGREGADO = 'renglon_agregado';

    public const RENGLON_QUITADO = 'renglon_quitado';

    public const OBRAS = '_obras';

    private const CAMPOS_DE_RENGLON = ['concepto', 'cantidad', 'precio', 'obra', 'rubro', 'material', 'detalle', 'fecha'];

    public function __construct(
        private GuardarRenglones $renglones,
        private FotoDeRenglon $foto,
        private RegistroActividad $actividad,
    ) {}

    /** true si cambió algo. El gasto queda con la cabecera nueva sin guardar (lo guarda el caso de uso). */
    public function aplicar(Gasto $gasto, GastoAGuardar $nuevo, int $userId): bool
    {
        $cabecera = $this->cabecera($gasto, $nuevo);
        $renglones = $this->renglones($gasto, $nuevo, $userId);

        return $cabecera || $renglones;
    }

    private function cabecera(Gasto $gasto, GastoAGuardar $nuevo): bool
    {
        $antes = $this->valores($gasto);
        $gasto->fill([
            'numero' => $nuevo->numero,
            'fecha' => $nuevo->fecha,
            'proveedor_id' => $nuevo->proveedorId,
            'rendido_por' => $nuevo->rendidoPor,
            'periodo_desde' => $nuevo->periodoDesde,
            'periodo_hasta' => $nuevo->periodoHasta,
        ]);
        $gasto->unsetRelation('proveedor');
        $cambios = self::diferencias($antes, $this->valores($gasto));

        if ($cambios !== []) {
            $this->actividad->registrar($gasto, self::EDITADO, $cambios);
        }

        return $cambios !== [];
    }

    private function renglones(Gasto $gasto, GastoAGuardar $nuevo, int $userId): bool
    {
        $guardados = $gasto->renglones->keyBy('id');
        $hubo = false;

        foreach ($nuevo->renglones as $indice => $renglon) {
            $fila = $renglon->id === null ? null : $guardados->pull($renglon->id);

            if ($fila instanceof GastoRenglon) {
                $antes = $this->foto->de($fila);
                $obraAntes = $fila->obra_id;
                $this->renglones->escribir($fila, $renglon, $indice + 1, $userId);
                $hubo = $this->cambiado($gasto, $antes, $this->foto->de($fila), [$obraAntes, $fila->obra_id]) || $hubo;

                continue;
            }

            $fila = $this->renglones->escribir(new GastoRenglon(['gasto_id' => $gasto->id]), $renglon, $indice + 1, $userId);
            $this->actividad->registrar($gasto, self::RENGLON_AGREGADO, [
                'renglon' => ['antes' => null, 'despues' => $this->foto->resumen($fila)],
                self::OBRAS => ['antes' => null, 'despues' => [$fila->obra_id]],
            ]);
            $hubo = true;
        }

        foreach ($guardados as $quitado) {
            $quitado->delete();
            $this->actividad->registrar($gasto, self::RENGLON_QUITADO, [
                'renglon' => ['antes' => $quitado->concepto, 'despues' => null],
                self::OBRAS => ['antes' => [$quitado->obra_id], 'despues' => null],
            ]);
            $hubo = true;
        }

        $gasto->unsetRelation('renglones');

        return $hubo;
    }

    /**
     * @param  array<string, string|null>  $antes
     * @param  array<string, string|null>  $despues
     * @param  list<int|null>  $obras
     */
    private function cambiado(Gasto $gasto, array $antes, array $despues, array $obras): bool
    {
        $cambios = self::diferencias(
            array_intersect_key($antes, array_flip(self::CAMPOS_DE_RENGLON)),
            array_intersect_key($despues, array_flip(self::CAMPOS_DE_RENGLON)),
        );

        if ($cambios === []) {
            return false;
        }

        if ($antes['total'] !== $despues['total']) {
            $cambios['total'] = ['antes' => $antes['total'], 'despues' => $despues['total']];
        }

        $this->actividad->registrar($gasto, self::RENGLON_CAMBIADO, [
            'renglon' => ['antes' => $antes['concepto'], 'despues' => $despues['concepto']],
            ...$cambios,
            self::OBRAS => ['antes' => [$obras[0]], 'despues' => [$obras[1]]],
        ]);

        return true;
    }

    /**
     * @return array<string, string|null>
     */
    private function valores(Gasto $gasto): array
    {
        return [
            'numero' => $gasto->numero,
            'fecha' => Legible::fecha($gasto->fecha),
            'proveedor' => $gasto->proveedor->nombre,
            'rendido_por' => $gasto->rendido_por,
            'periodo' => $gasto->periodo_desde === null ? null : EtiquetaDocumento::periodo($gasto->periodo_desde, $gasto->periodo_hasta),
        ];
    }

    /**
     * @param  array<string, string|null>  $antes
     * @param  array<string, string|null>  $despues
     * @return array<string, array{antes: string|null, despues: string|null}>
     */
    private static function diferencias(array $antes, array $despues): array
    {
        $cambios = [];

        foreach ($despues as $campo => $valor) {
            if (($antes[$campo] ?? null) !== $valor) {
                $cambios[$campo] = ['antes' => $antes[$campo] ?? null, 'despues' => $valor];
            }
        }

        return $cambios;
    }
}
