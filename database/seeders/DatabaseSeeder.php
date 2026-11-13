<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Datos ficticios para desarrollo (los nombres y mails de las maquetas). Sectores, funciones y la
 * matriz inicial ya vienen de las migraciones. Contraseña de todos: UserFactory::PASSWORD.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $diego = $this->usuario('Diego', 'Gerencia de Administración');
        $hernan = $this->usuario('Hernán', 'Jefatura de Proyectos');
        $this->usuario('Laura', 'Ingeniería de proyectos', $hernan);
        $sofia = $this->usuario('Sofía', 'Ingeniería de proyectos', $hernan);
        $this->usuario('Pablo', 'Ingeniería de proyectos', $sofia);
        $this->usuario('Carla', 'Compras', $diego);
    }

    private function usuario(string $nombre, string $sector, ?User $jefe = null): User
    {
        $email = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre)).'@empresa-ejemplo.com';
        $factory = User::factory()->enSector($sector);

        return ($jefe === null ? $factory : $factory->dependeDe($jefe))->create([
            'nombre' => $nombre,
            'email' => $email,
        ]);
    }
}
