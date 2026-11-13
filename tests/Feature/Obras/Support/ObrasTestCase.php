<?php

declare(strict_types=1);

namespace Tests\Feature\Obras\Support;

use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Shared\Adjuntos\Support\ArchivosDePrueba;
use Tests\TestCase;

/**
 * Base de los tests de Obras: MySQL real con la matriz inicial de la migración (identidad.md §6.3),
 * disco de adjuntos falso y personas ficticias por sector. Se autentica con `actingAs` (sin contraseña).
 */
abstract class ObrasTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('adjuntos');
        config(['adjuntos.disk' => 'adjuntos']);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    protected function usuarioDe(string $sector, array $atributos = []): User
    {
        return User::factory()->enSector($sector)->create($atributos);
    }

    /** Ingeniería de proyectos: Obras = Editar (todas), Presupuesto por rubro = Ver solo sus obras, Estado = Ver. */
    protected function laura(): User
    {
        return $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Laura']);
    }

    /** Jefatura de Administración: Obras, Estado, Presupuesto por rubro y Monto de obra = Editar. */
    protected function diego(): User
    {
        return $this->usuarioDe('Jefatura de Administración', ['nombre' => 'Diego']);
    }

    /** Compras: Obras = Ver, Presupuesto por rubro = Editar, Monto de obra = Sin acceso. */
    protected function carla(): User
    {
        return $this->usuarioDe('Compras', ['nombre' => 'Carla']);
    }

    /** Jefatura de Proyectos: Obras = Editar, Estado de obra = Ver, Monto de obra = Sin acceso. */
    protected function hernan(): User
    {
        return $this->usuarioDe('Jefatura de Proyectos', ['nombre' => 'Hernán']);
    }

    /** Sin ninguna celda: no ve Obras. */
    protected function sinAcceso(): User
    {
        return User::factory()->create(['nombre' => 'Visitante']);
    }

    protected function celda(string $sector, string $funcion, string $nivel, string $alcance = 'todas'): void
    {
        Permiso::query()
            ->where('sector_id', Sector::query()->where('nombre', $sector)->valueOrFail('id'))
            ->where('funcion_id', Funcion::query()->where('clave', $funcion)->valueOrFail('id'))
            ->update(['nivel' => $nivel, 'alcance' => $alcance]);
    }

    protected function obraDe(User $responsable, ObraEstado $estado = ObraEstado::Confirmada): Obra
    {
        return Obra::factory()->deResponsable($responsable)->enEstado($estado)->create();
    }

    protected function subir(User $user, string $contexto, ?UploadedFile $archivo = null): int
    {
        $id = $this->actingAs($user)
            ->post('/api/adjuntos', ['contexto' => $contexto, 'archivo' => $archivo ?? ArchivosDePrueba::pdf()], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    protected function link(User $user, string $contexto, string $url = 'https://drive.example/carpeta/oc-ejemplo'): int
    {
        $id = $this->actingAs($user)->postJson('/api/adjuntos', ['contexto' => $contexto, 'url' => $url])->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    protected function adjunto(int $id): Adjunto
    {
        return Adjunto::query()->findOrFail($id);
    }

    /**
     * ObraAlta del caso borde 1 (datos ficticios).
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function alta(User $responsable, int $oferta, array $cambios = []): array
    {
        return array_replace_recursive([
            'nombre_trabajo' => 'Piso industrial nave 3',
            'cliente' => 'Planta Ejemplo SA',
            'planta' => 'Nave 3',
            'responsable_id' => $responsable->id,
            'fecha_inicio_prevista' => null,
            'fecha_fin_prevista' => null,
            'carpeta_drive_url' => null,
            'presupuesto' => ['numero' => '2-131-2026', 'revision' => 'REV01', 'oferta' => $oferta],
            'adicionales' => [],
            'contactos' => [
                'solicitante' => ['nombre' => 'Jefe de mantenimiento (ejemplo)', 'email' => 'mantenimiento@planta-ejemplo.com', 'telefono' => '291 000-0000'],
                'comprador' => ['nombre' => 'Analista de compras (ejemplo)', 'email' => 'compras@planta-ejemplo.com', 'telefono' => '291 000-0001'],
            ],
        ], $cambios);
    }

    /**
     * Alta completa por API; devuelve la obra creada.
     *
     * @param  array<string, mixed>  $cambios
     */
    protected function crearObra(User $actor, ?User $responsable = null, array $cambios = []): Obra
    {
        $oferta = $this->subir($actor, 'presupuesto');
        $id = $this->actingAs($actor)->postJson('/api/obras', $this->alta($responsable ?? $actor, $oferta, $cambios))
            ->assertCreated()
            ->json('data.id');
        $this->assertIsInt($id);

        return Obra::query()->findOrFail($id);
    }
}
