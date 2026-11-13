<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identidad\Domain\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Sector ficticio, sin celdas en la matriz (para la matriz real usar los de la migración).
 *
 * @extends Factory<Sector>
 */
class SectorFactory extends Factory
{
    protected $model = Sector::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Sector '.fake()->unique()->bothify('????-####'),
            'es_direccion' => false,
            'activo' => true,
        ];
    }

    public function archivado(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
