<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Resources;

use App\Modules\Identidad\Domain\Models\BloqueoIngreso;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Concurrencia\VersionDeFila;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario de W-15/W-39. El detalle suma lo que tiene a cargo (RN-IDE-24).
 *
 * @property User $resource
 */
final class UsuarioResource extends JsonResource
{
    /** @var array{personas_a_cargo: int, obras_como_responsable: int, pedidos_abiertos: int, pedidos_en_el_sector: int}|null */
    private ?array $aCargo = null;

    /**
     * Detalle de W-39: suma personas a cargo y obras como responsable.
     *
     * @param  array{personas_a_cargo: int, obras_como_responsable: int, pedidos_abiertos: int, pedidos_en_el_sector: int}  $aCargo
     */
    public static function detalle(User $user, array $aCargo): self
    {
        $resource = new self($user);
        $resource->aCargo = $aCargo;

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $jefe = $user->jefe;

        return [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'activo' => $user->activo,
            'sector' => ['id' => $user->sector->id, 'nombre' => $user->sector->nombre],
            'jefe' => $jefe === null ? null : ['id' => $jefe->id, 'nombre' => $jefe->nombre],
            // PU-045: si no puede pedir código hasta cierta hora; gerencia lo puede destrabar.
            'bloqueo_ingreso' => self::bloqueoActivo($user->bloqueoIngreso),
            // Versión para el bloqueo optimista de la edición (PU-002).
            'updated_at' => VersionDeFila::iso($user->updated_at),
            ...($this->aCargo ?? []),
        ];
    }

    /**
     * @return array{hasta: string}|null
     */
    private static function bloqueoActivo(?BloqueoIngreso $bloqueo): ?array
    {
        return $bloqueo !== null && $bloqueo->bloqueado() && $bloqueo->bloqueado_hasta !== null
            ? ['hasta' => $bloqueo->bloqueado_hasta->toIso8601String()]
            : null;
    }
}
