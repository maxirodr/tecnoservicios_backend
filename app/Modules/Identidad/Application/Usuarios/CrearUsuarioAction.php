<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\MailsDeCuenta;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;

/**
 * F-58 / HU-005: alta de usuario activo, sin contraseña; recibe el mail de bienvenida para
 * elegirla (RN-IDE-09). El mail sale después del commit.
 */
final class CrearUsuarioAction
{
    public function __construct(
        private readonly ReglasDeJefatura $jefatura,
        private readonly MailsDeCuenta $mails,
        private readonly RegistroActividad $actividad,
    ) {}

    /**
     * @param  array{nombre: string, email: string, sector_id: int, jefe_id?: int|null, telefono?: string|null}  $datos
     */
    public function handle(User $actor, array $datos): User
    {
        $jefeId = $datos['jefe_id'] ?? null;

        if ($jefeId !== null) {
            $this->jefatura->validar(null, $jefeId);
        }

        return DB::transaction(function () use ($actor, $datos, $jefeId): User {
            $user = User::query()->create([
                'nombre' => $datos['nombre'],
                'email' => MailNormalizado::de($datos['email']),
                'sector_id' => $datos['sector_id'],
                'jefe_id' => $jefeId,
                'telefono' => $datos['telefono'] ?? null,
                'activo' => true,
            ]);

            $this->actividad->registrar($user, 'creado');
            $this->mails->enviarBienvenida($user, $actor->nombre);

            return $user;
        });
    }
}
