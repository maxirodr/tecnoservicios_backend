<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Domain\ProveedorDuplicado;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * F-56: edición con quién y cuándo (HU-021 crit. 5). El genérico no se modifica (RN-PRV-13). Guardar
 * sin cambios no registra actividad (F-14).
 */
final readonly class EditarProveedorAction
{
    public function __construct(
        private UnicidadDeProveedor $unicidad,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @param  array<string, mixed>  $datos  solo las columnas que vinieron
     *
     * @throws ProveedorDuplicado
     */
    public function handle(User $autor, Proveedor $proveedor, array $datos): Proveedor
    {
        if ($proveedor->es_generico) {
            throw ValidationException::withMessages(['proveedor' => 'El proveedor genérico no se modifica.']);
        }

        return $this->unicidad->guardar($datos, $proveedor->id, fn (): Proveedor => DB::transaction(function () use ($autor, $proveedor, $datos): Proveedor {
            $antes = $proveedor->only(array_keys($datos));
            $proveedor->fill($datos);

            if (! $proveedor->isDirty()) {
                return $proveedor;
            }

            $proveedor->updated_by = $autor->id;
            $proveedor->save();

            $cambios = [];
            foreach (array_keys($datos) as $campo) {
                if ($proveedor->wasChanged($campo)) {
                    $cambios[$campo] = ['antes' => $antes[$campo], 'despues' => $proveedor->getAttribute($campo)];
                }
            }
            $this->actividad->registrar($proveedor, 'editado', $cambios);

            return $proveedor;
        }));
    }
}
