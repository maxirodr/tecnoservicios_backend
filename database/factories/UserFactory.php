<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Usuarios ficticios. Por defecto en un sector nuevo sin celdas (sin acceso a nada):
 * para probar permisos usar `enSector('Compras')` (sectores y matriz de la migración).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public const PASSWORD = 'contrasena-de-prueba';

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make(self::PASSWORD),
            'telefono' => null,
            'activo' => true,
            'sector_id' => Sector::factory(),
            'jefe_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /** En un sector de la configuración inicial (§6.2), por nombre. */
    public function enSector(string $nombre): static
    {
        return $this->state(fn (): array => [
            'sector_id' => Sector::query()->where('nombre', $nombre)->valueOrFail('id'),
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }

    /** Dado de alta pero sin usar todavía el mail de bienvenida. */
    public function sinContrasena(): static
    {
        return $this->state(fn (): array => ['password' => null]);
    }

    public function dependeDe(User $jefe): static
    {
        return $this->state(fn (): array => ['jefe_id' => $jefe->id]);
    }
}
