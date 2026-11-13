<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Modules\Proveedores\Contracts\ProveedorId;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** F-42 (combobox de la factura, alta rápida) · F-44 (genérico) · proveedores.md §10.2. */
final class OpcionesYConsultaTest extends ProveedoresTestCase
{
    public function test_opciones_devuelve_lo_necesario_para_elegir_en_la_factura(): void
    {
        $corralon = Proveedor::factory()->llamado('Corralón Centro')->conCuit('30000000007')->deRubro('materiales')->create();
        Proveedor::factory()->count(25)->create();

        $this->actingAs($this->quienEdita())->getJson('/api/proveedores/opciones?buscar=corralon')
            ->assertOk()
            ->assertJsonPath('data', [[
                'id' => $corralon->id,
                'nombre' => 'Corralón Centro',
                'cuit' => '30-00000000-7',
                'rubro_habitual' => ['clave' => 'materiales', 'nombre' => 'MATERIALES'],
                'es_generico' => false,
            ]]);

        $this->actingAs($this->quienEdita())->getJson('/api/proveedores/opciones')->assertOk()->assertJsonCount(20, 'data');
    }

    public function test_opciones_las_usa_quien_ve_proveedores_o_carga_facturas(): void
    {
        $this->actingAs($this->quienVe())->getJson('/api/proveedores/opciones')->assertOk();

        // Gastos = editar sin acceso a Proveedores: elige el proveedor de la factura igual.
        $this->celda(self::SECTOR_SIN_ACCESO, 'gastos', 'editar');
        $this->actingAs($this->sinAcceso())->getJson('/api/proveedores/opciones')->assertOk();
        $this->actingAs($this->sinAcceso())->getJson('/api/proveedores')->assertForbidden();
    }

    public function test_opciones_sin_proveedores_ni_gastos_editar_es_403(): void
    {
        // Ingeniería: Proveedores sin acceso y Gastos = ver (solo sus obras).
        $this->actingAs($this->sinAcceso())->getJson('/api/proveedores/opciones')->assertForbidden();
        $this->actingAs($this->usuarioDe('Calidad'))->getJson('/api/proveedores/opciones')->assertForbidden();
    }

    public function test_migracion_crea_un_solo_proveedor_generico(): void
    {
        $this->assertSame(1, Proveedor::query()->where('es_generico', true)->count());

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('proveedores')->insert([
            'nombre' => 'Otro genérico', 'nombre_clave' => 'otro generico', 'nombre_normalizado' => 'otro generico',
            'nombre_busqueda' => 'otro generico', 'es_generico' => true,
        ]);
    }

    public function test_proveedor_generico_se_llama_gastos_sin_comprobante(): void
    {
        $generico = Proveedor::query()->where('es_generico', true)->sole();

        $this->assertSame('Gastos sin comprobante', $generico->nombre);
        $this->assertNull($generico->cuit);
        $this->assertTrue($generico->activo);
    }

    public function test_no_se_puede_crear_otro_con_el_nombre_del_generico(): void
    {
        $this->actingAs($this->quienEdita())->postJson('/api/proveedores', ['nombre' => 'GASTOS SIN COMPROBANTE'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', 'Ya existe Gastos sin comprobante.');
    }

    public function test_consulta_expone_el_generico(): void
    {
        $consulta = app(ProveedoresConsulta::class);
        $generico = $consulta->generico();
        $corralon = Proveedor::factory()->llamado('Corralón Centro')->conCuit('30000000007')->deRubro('materiales')->create();

        $this->assertTrue($consulta->esGenerico($generico));
        $this->assertFalse($consulta->esGenerico(new ProveedorId($corralon->id)));
        $this->assertTrue($consulta->existe(new ProveedorId($corralon->id)));
        $this->assertFalse($consulta->existe(new ProveedorId(999999)));
        $this->assertNull($consulta->resumen(new ProveedorId(999999)));

        $resumen = $consulta->resumen(new ProveedorId($corralon->id));
        $this->assertNotNull($resumen);
        $this->assertSame('Corralón Centro', $resumen->nombre);
        $this->assertSame('30-00000000-7', $resumen->cuit);
        $this->assertSame('materiales', $resumen->rubroHabitual);
    }

    public function test_migracion_carga_los_trece_rubros_en_orden(): void
    {
        $rubros = DB::table('rubros')->orderBy('orden')->pluck('clave')->all();

        $this->assertSame([
            'materiales', 'insumos', 'epp', 'subcontratos', 'logistica', 'amortizacion', 'mantenimiento',
            'obradores_banos', 'servicios_generales', 'maquinas_herramientas', 'viaticos', 'fijos', 'horas_liquidadas',
        ], $rubros);
        $this->assertSame(
            DB::table('rubros')->where('clave', 'insumos')->value('id'),
            DB::table('rubros')->where('clave', 'epp')->value('resume_en_rubro_id'),
        );
    }
}
