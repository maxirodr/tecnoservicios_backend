<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Domain\ProveedorDuplicado;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;

/**
 * F-56 / F-42 (alta rápida, mismo endpoint): solo el nombre es obligatorio (RN-PRV-05); nombre y CUIT
 * no se repiten (RN-PRV-04/07). Los parecidos avisan pero no bloquean (RN-PRV-08).
 */
final readonly class CrearProveedorAction
{
    public function __construct(
        private UnicidadDeProveedor $unicidad,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @param  array<string, mixed>  $datos  columnas ya normalizadas por el FormRequest
     *
     * @throws ProveedorDuplicado
     */
    public function handle(User $autor, array $datos): Proveedor
    {
        return $this->unicidad->guardar($datos, null, fn (): Proveedor => DB::transaction(function () use ($autor, $datos): Proveedor {
            $proveedor = Proveedor::query()->create([...$datos, 'created_by' => $autor->id, 'updated_by' => $autor->id]);

            $this->actividad->registrar($proveedor, 'creado');

            return $proveedor;
        }));
    }
}
