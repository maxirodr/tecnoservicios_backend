<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos\Support;

use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Shared\Adjuntos\Support\ArchivosDePrueba;
use Tests\TestCase;

/**
 * Base de los tests de Pedidos: MySQL real con la matriz inicial (Pedidos = Editar·todas en los 11
 * sectores), disco de adjuntos falso, Avisos en memoria y "hoy" fijo: lunes 19-oct-2026, 10:00 en
 * Argentina (13:00 UTC). Personas y textos de las maquetas (ficticios).
 */
abstract class PedidosTestCase extends TestCase
{
    use RefreshDatabase;

    protected const HOY = '2026-10-19';

    protected AvisosFalsos $avisos;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-19 13:00:00', 'UTC'));
        Storage::fake('adjuntos');
        config(['adjuntos.disk' => 'adjuntos']);
        $this->avisos = AvisosFalsos::registrar($this->app);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    protected function persona(string $nombre, string $sector, array $atributos = []): User
    {
        return User::factory()->enSector($sector)->create(['nombre' => $nombre, ...$atributos]);
    }

    protected function laura(): User
    {
        return $this->persona('Laura', 'Ingeniería de proyectos');
    }

    protected function carla(): User
    {
        return $this->persona('Carla', 'Compras');
    }

    protected function sofia(): User
    {
        return $this->persona('Sofía', 'Calidad');
    }

    protected function diego(): User
    {
        return $this->persona('Diego', 'Jefatura de Administración');
    }

    protected function sector(string $nombre): Sector
    {
        return Sector::query()->where('nombre', $nombre)->firstOrFail();
    }

    protected function celda(string $sector, string $funcion, string $nivel, string $alcance = 'todas'): void
    {
        Permiso::query()
            ->where('sector_id', $this->sector($sector)->id)
            ->where('funcion_id', Funcion::query()->where('clave', $funcion)->valueOrFail('id'))
            ->update(['nivel' => $nivel, 'alcance' => $alcance]);
    }

    protected function obra(ObraEstado $estado = ObraEstado::EnEjecucion, string $nombre = 'Reparación de losa tanque B'): Obra
    {
        return Obra::factory()->enEstado($estado)->create(['nombre_trabajo' => $nombre]);
    }

    /**
     * PedidoAlta del caso borde 1 (datos ficticios).
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function alta(User $propietario, ?Obra $obra = null, array $cambios = []): array
    {
        return [
            'tarea' => 'Alquiler de andamio para el sector de bombas',
            'obra_id' => $obra?->id,
            'sector_id' => $propietario->sector_id,
            'propietario_id' => $propietario->id,
            'fecha_entrega' => '2026-10-23',
            'con_quien' => 'Alquileres Andamios Oeste',
            ...$cambios,
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    protected function crear(User $solicitante, User $propietario, ?Obra $obra = null, array $cambios = []): Pedido
    {
        $id = $this->actingAs($solicitante)->postJson('/api/pedidos', $this->alta($propietario, $obra, $cambios))
            ->assertCreated()
            ->json('data.id');
        $this->assertIsInt($id);

        return Pedido::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return TestResponse<JsonResponse>
     */
    protected function accion(User $user, Pedido $pedido, string $accion, array $datos = []): TestResponse
    {
        return $this->actingAs($user)->postJson("/api/pedidos/{$pedido->id}/{$accion}", $datos);
    }

    protected function link(User $user, string $contexto, string $url = 'https://drive.example/carpeta/ficha-tecnica'): int
    {
        $id = $this->actingAs($user)->postJson('/api/adjuntos', ['contexto' => $contexto, 'url' => $url])->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    protected function archivo(User $user, string $contexto): int
    {
        $id = $this->actingAs($user)
            ->post('/api/adjuntos', ['contexto' => $contexto, 'archivo' => ArchivosDePrueba::pdf()], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    protected function fingirEventos(): void
    {
        Event::fake();
    }
}
