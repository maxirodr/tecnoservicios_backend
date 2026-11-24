<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos\Support;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Shared\Actividad\Actividad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Base de los tests de Gastos: MySQL real con la matriz inicial (identidad.md §6.3), disco de adjuntos
 * falso, `MaterialesDeObra` falso (lo implementa Rendicion) y "hoy" fijo: lunes 19-oct-2026, 10:00 en
 * Argentina (13:00 UTC). Personas, obras, proveedores e importes de las maquetas (ficticios).
 */
abstract class GastosTestCase extends TestCase
{
    use RefreshDatabase;

    protected const HOY = '2026-10-19';

    protected MaterialesDeObraFalsos $materiales;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-19 13:00:00', 'UTC'));
        Storage::fake('adjuntos');
        config(['adjuntos.disk' => 'adjuntos']);
        $this->materiales = MaterialesDeObraFalsos::registrar($this->app);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function persona(string $nombre, string $sector): User
    {
        return User::factory()->enSector($sector)->create(['nombre' => $nombre]);
    }

    /** Jefatura de Administración: Gastos, Rendición y Control por cantidad = Editar·todas. */
    protected function diego(): User
    {
        return $this->persona('Diego', 'Jefatura de Administración');
    }

    /** Compras: Gastos = Editar·todas. */
    protected function carla(): User
    {
        return $this->persona('Carla', 'Compras');
    }

    /** Ingeniería de proyectos: Gastos = Ver·solo sus obras (DU-A04). */
    protected function laura(): User
    {
        return $this->persona('Laura', 'Ingeniería de proyectos');
    }

    /** Dirección: Gastos = Ver·todas. */
    protected function marta(): User
    {
        return $this->persona('Marta', 'Dirección');
    }

    /** Seguridad e Higiene: Gastos sin acceso. */
    protected function sofia(): User
    {
        return $this->persona('Sofía', 'Seguridad e Higiene');
    }

    protected function celda(string $sector, string $funcion, string $nivel, string $alcance = 'todas'): void
    {
        Permiso::query()
            ->where('sector_id', Sector::query()->where('nombre', $sector)->valueOrFail('id'))
            ->where('funcion_id', Funcion::query()->where('clave', $funcion)->valueOrFail('id'))
            ->update(['nivel' => $nivel, 'alcance' => $alcance]);
    }

    protected function obra(string $nombre, ?User $responsable = null, ObraEstado $estado = ObraEstado::EnEjecucion): Obra
    {
        $fabrica = Obra::factory()->enEstado($estado);

        return ($responsable === null ? $fabrica : $fabrica->deResponsable($responsable))->create(['nombre_trabajo' => $nombre]);
    }

    protected function proveedor(string $nombre = 'Corralón Centro'): Proveedor
    {
        return Proveedor::query()->where('nombre', $nombre)->first() ?? Proveedor::factory()->llamado($nombre)->create();
    }

    protected function generico(): Proveedor
    {
        return Proveedor::query()->where('es_generico', true)->firstOrFail();
    }

    protected function material(Obra $obra, string $nombre, string $unidad = 'bolsa', ?string $precio = null, bool $fuera = false): Material
    {
        return Material::query()->create([
            'obra_id' => $obra->id, 'nombre' => $nombre, 'nombre_clave' => Str::of($nombre)->ascii()->lower()->squish()->toString(),
            'unidad' => $unidad, 'cantidad_presupuestada' => $fuera ? null : '100.000', 'precio_presupuestado' => $precio,
            'fuera_de_presupuesto' => $fuera, 'created_by' => $obra->responsable_id, 'updated_by' => $obra->responsable_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function renglon(string $concepto, string $cantidad, string $precio, ?Obra $obra, ?string $rubro, array $cambios = []): array
    {
        return ['concepto' => $concepto, 'cantidad' => $cantidad, 'precio_unitario' => $precio, 'obra_id' => $obra?->id, 'rubro' => $rubro, ...$cambios];
    }

    /**
     * La factura de W-10 (§10 caso 1): 905.600 · Nave 3 549.000 · Losa tanque B 311.600 · sin obra 45.000.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function facturaDeW10(Proveedor $proveedor, Obra $nave, Obra $losa, array $cambios = []): array
    {
        return [
            'tipo_documento' => 'factura', 'origen' => 'factura', 'numero' => '3-12345', 'fecha' => '2026-10-17',
            'proveedor_id' => $proveedor->id,
            'renglones' => [
                $this->renglon('Cemento x 50 kg', '30', '9800.00', $nave, 'materiales'),
                $this->renglon('Arena gruesa (m³)', '6', '42500.00', $nave, 'materiales'),
                $this->renglon('Disco diamantado 350 mm', '2', '118000.00', $losa, 'maquinas_herramientas'),
                $this->renglon('Guantes de trabajo (par)', '24', '3150.00', $losa, 'insumos'),
                $this->renglon('Flete', '1', '45000.00', null, 'logistica'),
            ],
            ...$cambios,
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, string>  $headers
     * @return TestResponse<JsonResponse>
     */
    protected function cargar(User $user, array $datos, array $headers = []): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/gastos', $datos, $headers);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    protected function cargado(User $user, array $datos): Gasto
    {
        $id = $this->cargar($user, $datos)->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return Gasto::query()->with('renglones')->findOrFail($id);
    }

    protected function link(User $user, string $contexto, string $url = 'https://drive.example/facturas/factura-12345.pdf'): int
    {
        $id = $this->actingAs($user)->postJson('/api/adjuntos', ['contexto' => $contexto, 'url' => $url])->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    /**
     * Para `assertJsonPath('errors', …)`: los errores de renglón tienen puntos en la clave ("renglones.2.rubro").
     *
     * @return \Closure(array<string, list<string>>): bool
     */
    protected function error(string $campo, string $mensaje): \Closure
    {
        return function (array $errores) use ($campo, $mensaje): bool {
            $this->assertSame($mensaje, $errores[$campo][0] ?? null, 'Errores: '.json_encode($errores, JSON_UNESCAPED_UNICODE));

            return true;
        };
    }

    /**
     * @return list<string>
     */
    protected function acciones(Gasto $gasto): array
    {
        return array_values(Actividad::query()->where('sujeto_type', 'gasto')->where('sujeto_id', $gasto->id)->orderBy('id')->pluck('accion')->all());
    }
}
