<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Rendicion\Domain\Events\MaterialCambiado;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Domain\TextoComparable;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * RN-REN-47, 49, 61 (HU-082): se corrige nombre, unidad, cantidad, precio o presupuesto, con quién, cuándo y
 * antes → después. Completarle la cantidad presupuestada a uno "fuera de presupuesto" lo pasa a la lista.
 * Con el `updated_at` que leyó la pantalla: si otro guardó antes, 409 y no se pisa nada.
 */
final readonly class CorregirMaterialAction
{
    public const ACCION = 'material_corregido';

    public function __construct(
        private Connection $db,
        private ReglasDeLaLista $reglas,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws ValidationException|HttpResponseException
     */
    public function handle(User $user, Material $material, DatosMaterial $datos, ?CarbonInterface $version): Material
    {
        $presupuestoId = $datos->trae('presupuesto_id') ? $this->reglas->presupuesto($material->obra_id, $datos->presupuestoId, false) : null;

        return $this->db->transaction(function () use ($user, $material, $datos, $version, $presupuestoId): Material {
            VersionDeFila::bloquearYVerificar($material, $version);
            $antes = HistorialDeMaterial::foto($material);

            if ($datos->trae('nombre') && $datos->nombre !== null) {
                $this->reglas->nombreLibre($material->obra_id, $datos->nombre, $material->id);
                $material->nombre = $datos->nombre;
                $material->nombre_clave = TextoComparable::de($datos->nombre);
            }

            if ($datos->trae('unidad') && $datos->unidad !== null) {
                $material->unidad = $datos->unidad;
            }

            if ($datos->trae('cantidad_presupuestada') && $datos->cantidadPresupuestada !== null) {
                $material->cantidad_presupuestada = $datos->cantidadPresupuestada;
                $material->fuera_de_presupuesto = false;
            }

            if ($datos->trae('precio_presupuestado')) {
                $material->precio_presupuestado = $datos->precioPresupuestado;
            }

            if ($datos->trae('presupuesto_id')) {
                $material->presupuesto_id = $presupuestoId;
            }

            $cambios = HistorialDeMaterial::diferencias($antes, HistorialDeMaterial::foto($material));

            if ($cambios === []) {
                return $material;
            }

            $material->updated_by = $user->id;

            try {
                $material->save();
            } catch (UniqueConstraintViolationException) {
                throw ReglasDeLaLista::repetido((string) $datos->nombre);
            }

            $this->actividad->registrar($material, self::ACCION, $cambios);
            $this->eventos->dispatch(new MaterialCambiado($material->id, $material->obra_id, MaterialCambiado::CORREGIDO));

            return $material;
        });
    }
}
