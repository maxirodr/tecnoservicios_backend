<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Perfil;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * RN-IDE-16: en Mi perfil la persona cambia solo su nombre y su teléfono. Si gerencia la cambió en
 * Usuarios desde que abrió el formulario, 409 y no se pisa nada (PU-108).
 */
final readonly class ActualizarPerfilAction
{
    public function __construct(private Connection $db) {}

    /**
     * @param  array{nombre?: string, telefono?: string|null}  $datos
     *
     * @throws HttpResponseException 409 si la persona cambió desde `$version`
     */
    public function handle(User $user, array $datos, ?CarbonInterface $version = null): User
    {
        return $this->db->transaction(function () use ($user, $datos, $version): User {
            VersionDeFila::bloquearYVerificar($user, $version);
            $user->fill(array_intersect_key($datos, array_flip(['nombre', 'telefono'])));
            $user->save();

            return $user;
        });
    }
}
