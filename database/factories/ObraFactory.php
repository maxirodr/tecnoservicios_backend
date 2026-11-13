<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraContacto;
use App\Modules\Obras\Domain\Models\Presupuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Obras ficticias (clientes, trabajos y contactos inventados). Nacen con su presupuesto principal
 * (`2-NNN-2026 REV01`, número único por obra) y los dos contactos.
 *
 * @extends Factory<Obra>
 */
class ObraFactory extends Factory
{
    protected $model = Obra::class;

    private const ANIO = 2026;

    private static int $siguienteNumero = 100;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_trabajo' => 'Trabajo de ejemplo '.fake()->unique()->numberBetween(1, 99999),
            'cliente' => 'Cliente Ejemplo SA',
            'planta' => null,
            'responsable_id' => User::factory(),
            'estado' => ObraEstado::Confirmada,
            'created_by' => fn (array $atributos): mixed => $atributos['responsable_id'],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Obra $obra): void {
            if (Presupuesto::query()->where('obra_id', $obra->id)->exists()) {
                return;
            }

            Presupuesto::query()->create([
                'obra_id' => $obra->id,
                'numero' => '2-'.self::$siguienteNumero++.'-'.self::ANIO,
                'revision' => 'REV01',
                'es_principal' => true,
                'created_by' => $obra->created_by,
            ]);

            foreach (['solicitante', 'comprador'] as $tipo) {
                ObraContacto::query()->create([
                    'obra_id' => $obra->id,
                    'tipo' => $tipo,
                    'nombre' => "Contacto {$tipo} (ejemplo)",
                    'email' => "{$tipo}@cliente-ejemplo.com",
                    'telefono' => '291 000-0000',
                ]);
            }
        });
    }

    public function deResponsable(User $user): static
    {
        return $this->state(fn (): array => ['responsable_id' => $user->id, 'created_by' => $user->id]);
    }

    public function enEstado(ObraEstado $estado): static
    {
        return $this->state(fn (): array => [
            'estado' => $estado,
            'terminada_at' => $estado === ObraEstado::Terminada ? now() : null,
        ]);
    }
}
