<?php

declare(strict_types=1);

namespace Tests\Feature\Avisos;

use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\FotoDelPedido;
use App\Modules\Pedidos\Domain\Events\Participantes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base de los tests de Avisos: MySQL real con las migraciones (sectores de la configuración inicial). Los
 * eventos de Pedidos se disparan a mano con el contrato compartido; la cola es `sync` en los tests, así que
 * el listener corre en el momento.
 */
abstract class AvisosTestCase extends TestCase
{
    use RefreshDatabase;

    private int $actividad = 1000;

    /**
     * @param  array<string, mixed>  $atributos
     */
    protected function persona(string $nombre, string $sector = 'Ingeniería de proyectos', array $atributos = []): User
    {
        return User::factory()->enSector($sector)->create(['nombre' => $nombre, ...$atributos]);
    }

    protected function sectorId(string $nombre): int
    {
        return (int) Sector::query()->where('nombre', $nombre)->valueOrFail('id');
    }

    /**
     * @param  list<int>  $seguidores
     */
    protected function participantes(?User $solicitante, ?User $propietario, array $seguidores = [], ?int $sectorId = null): Participantes
    {
        return new Participantes($solicitante?->id, $propietario?->id, $sectorId ?? $propietario->sector_id ?? 0, $seguidores);
    }

    protected function foto(string $tarea = 'Solicitud de materiales N.º 3', ?string $obra = 'Piso industrial nave 3', ?string $entrega = '2026-11-20', string $prioridad = 'normal'): FotoDelPedido
    {
        return new FotoDelPedido($tarea, $obra !== null ? 12 : null, $obra, $entrega, $prioridad, 'no_iniciada');
    }

    protected function actividad(): int
    {
        return ++$this->actividad;
    }

    /**
     * @return list<Aviso>
     */
    protected function avisosDe(User $user): array
    {
        return array_values(Aviso::query()->where('user_id', $user->id)->orderBy('id')->get()->all());
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    protected function aviso(User $user, array $atributos = []): Aviso
    {
        static $clave = 0;
        $clave++;

        return Aviso::query()->create([
            'user_id' => $user->id,
            'tipo' => 'asignado',
            'pedido_id' => 88,
            'actor_id' => null,
            'datos' => ['texto' => 'Laura te asignó Alquiler de obrador · Piso industrial nave 3', 'tarea' => 'Alquiler de obrador', 'obra' => ['id' => 12, 'nombre_trabajo' => 'Piso industrial nave 3']],
            'dedupe_key' => "prueba:{$clave}:{$user->id}",
            ...$atributos,
        ]);
    }
}
