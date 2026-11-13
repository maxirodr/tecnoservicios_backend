<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;

/**
 * F-58 / HU-005: alta de usuario activo. No se le manda nada: entra directo pidiendo un código
 * con su mail (ADR-015, RN-IDE-09).
 */
final class CrearUsuarioAction
{
    public function __construct(
        private readonly ReglasDeJefatura $jefatura,
        private readonly RegistroActividad $actividad,
    ) {}

    /**
     * @param  array{nombre: string, email: string, sector_id: int, jefe_id?: int|null, telefono?: string|null}  $datos
     */
    public function handle(array $datos): User
    {
        $jefeId = $datos['jefe_id'] ?? null;

        if ($jefeId !== null) {
            $this->jefatura->validar(null, $jefeId);
        }

        return DB::transaction(function () use ($datos, $jefeId): User {
            $user = User::query()->create([
                'nombre' => $datos['nombre'],
                'email' => MailNormalizado::de($datos['email']),
                'sector_id' => $datos['sector_id'],
                'jefe_id' => $jefeId,
                'telefono' => $datos['telefono'] ?? null,
                'activo' => true,
            ]);

            $this->actividad->registrar($user, 'creado');

            return $user;
        });
    }
}
