<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;

/**
 * Lo que necesita el formulario de carga (gastos.md §12.1): los rubros (los 11 que se imputan y los 2 por
 * período, RN-GAS-12), qué se carga sin documento (RN-GAS-17) y el selector de obras en cualquier estado
 * (RN-GAS-13), solo las suyas con alcance "solo sus obras", con `tiene_materiales` (RN-GAS-14).
 */
final readonly class OpcionesDeCargaQuery
{
    public const MAX_OBRAS = 20;

    /** Con alcance propio se piden más y se dejan las suyas (ObrasConsulta no filtra por responsable). */
    private const MAX_A_FILTRAR = 500;

    public function __construct(
        private Rubros $rubros,
        private AccesoGasto $acceso,
        private ObrasConsulta $obras,
        private MaterialesDeObra $materiales,
    ) {}

    /**
     * @return array{rubros: list<array{clave: string, nombre: string, se_imputa: bool, carga_por_periodo: bool}>, origenes_sin_documento: list<string>}
     */
    public function opciones(): array
    {
        return [
            'rubros' => array_map(static fn (RubroDelCatalogo $r): array => [
                'clave' => $r->clave,
                'nombre' => $r->nombre,
                'se_imputa' => ! $r->cargaPorPeriodo,
                'carga_por_periodo' => $r->cargaPorPeriodo,
            ], $this->rubros->todos()),
            'origenes_sin_documento' => Origen::sinDocumentoDeW35(),
        ];
    }

    /**
     * @return list<array{id: int, numero: string, nombre_trabajo: string, estado: string, tiene_materiales: bool}>
     */
    public function obras(User $user, ?string $texto): array
    {
        $propias = $this->obrasDeLaCarga($user);
        $opciones = $this->obras->opciones($texto, $propias === null ? self::MAX_OBRAS : self::MAX_A_FILTRAR);

        if ($propias !== null) {
            $opciones = array_slice(array_values(array_filter($opciones, static fn (ObraResumen $o): bool => in_array($o->id, $propias, true))), 0, self::MAX_OBRAS);
        }

        return array_map(fn (ObraResumen $o): array => [
            'id' => $o->id,
            'numero' => $o->numero,
            'nombre_trabajo' => $o->nombreTrabajo,
            'estado' => $o->estado,
            'tiene_materiales' => $this->materiales->opciones($o->id, null) !== [],
        ], $opciones);
    }

    /**
     * null = todas; si no, las obras que alcanzan sus celdas de carga en Editar.
     *
     * @return list<int>|null
     */
    private function obrasDeLaCarga(User $user): ?array
    {
        $obras = [];

        foreach ([ClaveFuncion::Gastos, ClaveFuncion::Rendicion, ClaveFuncion::ControlCantidad] as $funcion) {
            if (! $this->acceso->puede($user, $funcion, Nivel::Editar)) {
                continue;
            }

            $deLaCelda = $this->acceso->obrasDe($user, $funcion);

            if ($deLaCelda === null) {
                return null;
            }

            $obras = [...$obras, ...$deLaCelda];
        }

        return array_values(array_unique($obras));
    }
}
