<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Rendicion\Domain\Events\MaterialCambiado;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Domain\TextoComparable;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * RN-REN-47 (HU-082): administración agrega un material a la lista del presupuesto de la obra. Queda en
 * `actividades` (F-14). El doble envío lo resuelve el middleware `idempotent` (RN-REN-61).
 */
final readonly class AgregarMaterialAction
{
    public const ACCION = 'material_agregado';

    public function __construct(
        private Connection $db,
        private ReglasDeLaLista $reglas,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, ObraResumen $obra, DatosMaterial $datos): Material
    {
        $nombre = (string) $datos->nombre;
        $presupuestoId = $this->reglas->presupuesto($obra->id, $datos->presupuestoId, true);

        return $this->db->transaction(function () use ($user, $obra, $datos, $nombre, $presupuestoId): Material {
            $this->reglas->nombreLibre($obra->id, $nombre);

            try {
                $material = Material::query()->create([
                    'obra_id' => $obra->id,
                    'presupuesto_id' => $presupuestoId,
                    'nombre' => $nombre,
                    'nombre_clave' => TextoComparable::de($nombre),
                    'unidad' => (string) $datos->unidad,
                    'cantidad_presupuestada' => $datos->cantidadPresupuestada,
                    'precio_presupuestado' => $datos->precioPresupuestado,
                    'fuera_de_presupuesto' => $datos->cantidadPresupuestada === null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ReglasDeLaLista::repetido($nombre);
            }

            $this->actividad->registrar($material, self::ACCION, HistorialDeMaterial::alta($material));
            $this->eventos->dispatch(new MaterialCambiado($material->id, $obra->id, MaterialCambiado::AGREGADO));

            return $material;
        });
    }
}
