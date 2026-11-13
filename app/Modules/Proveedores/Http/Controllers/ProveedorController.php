<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Controllers;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Application\CrearProveedorAction;
use App\Modules\Proveedores\Application\EditarProveedorAction;
use App\Modules\Proveedores\Application\ListarProveedoresQuery;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Domain\ProveedorDuplicado;
use App\Modules\Proveedores\Http\Requests\BuscarProveedoresRequest;
use App\Modules\Proveedores\Http\Requests\GuardarProveedorRequest;
use App\Modules\Proveedores\Http\Resources\ProveedorResource;
use App\Modules\Proveedores\Http\Resources\ProveedorResumenResource;
use App\Shared\Http\ApiExceptionRenderer;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-55 · F-56 · W-14 · W-38: la base, la ficha, el alta (también la rápida, F-42) y la edición. */
final class ProveedorController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    private const NO_VALIDO = 422;

    public function __construct(private readonly Permisos $permisos) {}

    public function index(BuscarProveedoresRequest $request, ListarProveedoresQuery $listar): JsonResponse
    {
        return $this->ok(ProveedorResumenResource::collection($listar->handle($request->buscar())));
    }

    public function show(Request $request, int $proveedor): JsonResponse
    {
        return $this->ok($this->ficha($this->usuario($request), $this->buscar($proveedor)));
    }

    public function store(GuardarProveedorRequest $request, CrearProveedorAction $crear): JsonResponse
    {
        $user = $this->usuario($request);

        try {
            $proveedor = $crear->handle($user, $request->datos());
        } catch (ProveedorDuplicado $duplicado) {
            return $this->duplicado($duplicado);
        }

        return $this->created($this->ficha($user, $proveedor->load('rubroHabitual')), "Guardamos {$proveedor->nombre}.");
    }

    public function update(GuardarProveedorRequest $request, int $proveedor, EditarProveedorAction $editar): JsonResponse
    {
        $user = $this->usuario($request);

        try {
            $editado = $editar->handle($user, $this->buscar($proveedor), $request->datos());
        } catch (ProveedorDuplicado $duplicado) {
            return $this->duplicado($duplicado);
        }

        return $this->ok($this->ficha($user, $editado->load('rubroHabitual')), 'Guardamos los cambios.');
    }

    private function ficha(User $user, Proveedor $proveedor): ProveedorResource
    {
        return ProveedorResource::conPermisos(
            $proveedor,
            $this->permisos->puede($user, ClaveFuncion::Proveedores->value, Nivel::Editar),
            $this->permisos->puede($user, ClaveFuncion::Gastos->value, Nivel::Ver),
        );
    }

    /** RN-PRV-04/07: 422 con el campo y el proveedor que ya existe, para el link a su ficha. */
    private function duplicado(ProveedorDuplicado $duplicado): JsonResponse
    {
        return $this->fail(ApiExceptionRenderer::messageFor(self::NO_VALIDO), self::NO_VALIDO, [
            $duplicado->campo => [$duplicado->getMessage()],
            'existente' => ['id' => $duplicado->existenteId, 'nombre' => $duplicado->existenteNombre],
        ]);
    }

    private function buscar(int $id): Proveedor
    {
        return Proveedor::query()->with('rubroHabitual')->findOrFail($id);
    }
}
