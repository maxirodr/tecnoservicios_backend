<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Resources;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario de W-15/W-39. `eligio_contrasena` decide si se muestra "Reenviar mail de bienvenida"
 * (RN-IDE-09). El detalle suma lo que tiene a cargo (RN-IDE-24). Nunca viaja la contraseña.
 *
 * @property User $resource
 */
final class UsuarioResource extends JsonResource
{
    /** @var array{personas_a_cargo: int, obras_como_responsable: int}|null */
    private ?array $aCargo = null;

    /**
     * Detalle de W-39: suma personas a cargo y obras como responsable.
     *
     * @param  array{personas_a_cargo: int, obras_como_responsable: int}  $aCargo
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
            'eligio_contrasena' => $user->eligioContrasena(),
            'sector' => ['id' => $user->sector->id, 'nombre' => $user->sector->nombre],
            'jefe' => $jefe === null ? null : ['id' => $jefe->id, 'nombre' => $jefe->nombre],
            ...($this->aCargo ?? []),
        ];
    }
}
