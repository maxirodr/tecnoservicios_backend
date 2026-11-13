<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Resources;

use App\Modules\Identidad\Application\Perfil\UsuarioActual;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `UsuarioActual` de identidad.md §10: datos, sector, jefe, si tiene gente y las 12 funciones.
 *
 * @property UsuarioActual $resource
 */
final class UsuarioActualResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource->user;
        $jefe = $user->jefe;

        return [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'sector' => ['id' => $user->sector->id, 'nombre' => $user->sector->nombre],
            'jefe' => $jefe === null ? null : ['id' => $jefe->id, 'nombre' => $jefe->nombre, 'sector' => $jefe->sector->nombre],
            'tiene_gente' => $this->resource->tieneGente,
            'permisos' => array_map(
                static fn (array $celda): array => ['nivel' => $celda['nivel']->value, 'alcance' => $celda['alcance']->value],
                $this->resource->permisos,
            ),
        ];
    }
}
