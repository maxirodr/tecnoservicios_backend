<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Requests;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;

/**
 * `GET /api/proveedores/opciones?buscar=` (F-42): el combobox de la factura. Lo usa quien ve la base
 * (`proveedores` ≥ ver) o quien carga facturas (`gastos` = editar), proveedores.md §7. Son dos
 * celdas posibles, por eso no alcanza el middleware `permiso` de una sola.
 */
final class OpcionesProveedoresRequest extends ProveedoresRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $permisos = $this->container->make(Permisos::class);

        return $user instanceof User && (
            $permisos->puede($user, ClaveFuncion::Proveedores->value, Nivel::Ver)
            || $permisos->puede($user, ClaveFuncion::Gastos->value, Nivel::Editar)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:150']];
    }

    public function buscar(): ?string
    {
        return $this->textoOpcional('buscar');
    }
}
