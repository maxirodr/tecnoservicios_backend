<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure\Console;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\MailsDeCuenta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * DU-A10: primer usuario en producción. Lo crea en el sector de Dirección y le manda el mail para
 * elegir su contraseña. Se corre una vez en el deploy; el mail no queda escrito en el repo.
 */
final class CrearAdministradorCommand extends Command
{
    protected $signature = 'identidad:crear-administrador {email : Mail de ingreso} {nombre : Nombre de la persona}';

    protected $description = 'Crea el primer usuario (sector Dirección) y le manda el mail para elegir su contraseña';

    public function handle(MailsDeCuenta $mails): int
    {
        $email = MailNormalizado::de((string) $this->argument('email'));
        $nombre = trim((string) $this->argument('nombre'));

        $validacion = Validator::make(['email' => $email, 'nombre' => $nombre], [
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'nombre' => ['required', 'max:120'],
        ]);

        if ($validacion->fails()) {
            $this->error('No se creó: el mail no es válido, ya existe, o falta el nombre.');

            return self::FAILURE;
        }

        $direccion = Sector::query()->where('es_direccion', true)->where('activo', true)->first();

        if ($direccion === null) {
            $this->error('No existe el sector Dirección: corré las migraciones primero.');

            return self::FAILURE;
        }

        $user = User::query()->create([
            'nombre' => $nombre,
            'email' => $email,
            'sector_id' => $direccion->id,
            'activo' => true,
        ]);

        $mails->enviarBienvenida($user, null);
        $this->info("Listo: {$email} quedó en Dirección y tiene el mail para elegir su contraseña.");

        return self::SUCCESS;
    }
}
