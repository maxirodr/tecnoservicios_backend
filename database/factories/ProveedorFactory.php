<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Rendicion\Domain\Models\Rubro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Proveedores ficticios (nunca nombres ni CUIT reales). Por defecto solo con nombre (RN-PRV-05).
 *
 * @extends Factory<Proveedor>
 */
class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Proveedor Ejemplo '.fake()->unique()->numerify('####'),
            'cuit' => null,
            'telefono' => null,
            'email' => null,
            'rubro_habitual_id' => null,
        ];
    }

    public function llamado(string $nombre): static
    {
        return $this->state(fn (): array => ['nombre' => $nombre]);
    }

    /** CUIT ficticio de 11 dígitos ya normalizado (p. ej. `30000000007`). */
    public function conCuit(string $digitos): static
    {
        return $this->state(fn (): array => ['cuit' => $digitos]);
    }

    public function deRubro(string $clave): static
    {
        return $this->state(fn (): array => [
            'rubro_habitual_id' => Rubro::query()->where('clave', $clave)->valueOrFail('id'),
        ]);
    }
}
