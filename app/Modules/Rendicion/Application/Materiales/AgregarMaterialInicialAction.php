<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Rendicion\Contracts\MaterialAgregado;
use App\Modules\Rendicion\Contracts\MaterialInicial;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Domain\TextoComparable;
use App\Modules\Rendicion\Infrastructure\MaterialesDeObraEloquent;
use App\Shared\CargaInicial\CargaRechazada;

/**
 * La lista de materiales de la carga inicial (`importacion.md` RN-IMP-28, resuelta DU-I06): por el mismo caso de uso
 * de W-48 (`AgregarMaterialAction`, con sus validaciones y su actividad). Un material que ya está por su nombre
 * normalizado no se duplica ni se cambia: se devuelve con su cantidad.
 */
final readonly class AgregarMaterialInicialAction
{
    public function __construct(
        private ObrasConsulta $obras,
        private AgregarMaterialAction $agregar,
    ) {}

    /**
     * @throws CargaRechazada
     */
    public function handle(int $actorId, int $obraId, MaterialInicial $material): MaterialAgregado
    {
        $existente = Material::query()
            ->where('obra_id', $obraId)
            ->where('nombre_clave', TextoComparable::claveDeMaterial($material->nombre))
            ->first();

        if ($existente instanceof Material) {
            return new MaterialAgregado($existente->id, false, $existente->cantidad_presupuestada);
        }

        self::exigirLargos($material);
        $obra = $this->obras->resumen($obraId) ?? throw CargaRechazada::por('La obra no está en el sistema.');
        $actor = User::query()->findOrFail($actorId);

        $nuevo = CargaRechazada::traducir(fn (): Material => $this->agregar->handle($actor, $obra, new DatosMaterial(
            $material->nombre,
            $material->unidad,
            $material->cantidadPresupuestada,
            $material->precioPresupuestado,
            $material->presupuestoId,
        )));

        return new MaterialAgregado($nuevo->id, true, null);
    }

    /** Los mismos topes que el formulario de W-48 (`GuardarMaterialRequest`). */
    private static function exigirLargos(MaterialInicial $material): void
    {
        if (mb_strlen($material->nombre) > MaterialesDeObraEloquent::NOMBRE_MAXIMO) {
            throw CargaRechazada::por('El material tiene más de '.MaterialesDeObraEloquent::NOMBRE_MAXIMO.' caracteres.');
        }

        if (mb_strlen($material->unidad) > MaterialesDeObraEloquent::UNIDAD_MAXIMA) {
            throw CargaRechazada::por('La unidad tiene más de '.MaterialesDeObraEloquent::UNIDAD_MAXIMA.' caracteres.');
        }
    }
}
