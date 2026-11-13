<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * F-58 / HU-006: editar datos, sector, jefatura y activo/inactivo. No se borra a nadie
 * (RN-IDE-20). Al desactivar, el middleware de usuario activo corta sus sesiones en el próximo
 * pedido (RN-IDE-10). Guardas de RN-IDE-22 y RN-IDE-25.
 */
final class ActualizarUsuarioAction
{
    public const NO_SE_DESACTIVA_A_SI_MISMO = 'No te podés desactivar a vos mismo.';

    private const CAMPOS = ['nombre', 'email', 'sector_id', 'jefe_id', 'telefono', 'activo'];

    public function __construct(
        private readonly ReglasDeJefatura $jefatura,
        private readonly GuardaAdministradores $administradores,
        private readonly RegistroActividad $actividad,
    ) {}

    /**
     * @param  array{nombre?: string, email?: string, sector_id?: int, jefe_id?: int|null, telefono?: string|null, activo?: bool}  $datos
     */
    public function handle(User $actor, User $usuario, array $datos): User
    {
        $datos = array_intersect_key($datos, array_flip(self::CAMPOS));

        if (($datos['activo'] ?? true) === false && $usuario->id === $actor->id) {
            throw ValidationException::withMessages(['activo' => [self::NO_SE_DESACTIVA_A_SI_MISMO]]);
        }

        $jefeId = $datos['jefe_id'] ?? null;
        if ($jefeId !== null && $jefeId !== $usuario->jefe_id) {
            $this->jefatura->validar($usuario, $jefeId);
        }

        if (isset($datos['email'])) {
            $datos['email'] = MailNormalizado::de($datos['email']);
        }

        return DB::transaction(function () use ($usuario, $datos): User {
            $usuario->fill($datos);
            $cambios = $this->cambios($usuario);

            $usuario->save();

            if (isset($cambios['activo']) || isset($cambios['sector_id'])) {
                $this->administradores->asegurar(isset($cambios['activo']) ? 'activo' : 'sector_id');
            }

            if ($cambios !== []) {
                $this->actividad->registrar($usuario, 'editado', $cambios);
            }

            return $usuario;
        });
    }

    /**
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    private function cambios(User $usuario): array
    {
        $cambios = [];

        foreach (array_keys($usuario->getDirty()) as $campo) {
            $cambios[$campo] = ['antes' => $usuario->getOriginal($campo), 'despues' => $usuario->getAttribute($campo)];
        }

        return $cambios;
    }
}
