<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Contratos compartidos de la semana 3 (rendicion.md §12.4): catálogo y materiales reales; Gastos, nula hasta que cargue la suya. */
final class ContratosDeLaSemana3Test extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_trae_los_13_imputables_en_el_orden_de_su_general_y_los_12_presupuestables(): void
    {
        $catalogo = $this->app->make(CatalogoDeRubros::class);

        $imputables = $catalogo->imputables();
        $this->assertCount(13, $imputables);
        $this->assertSame('materiales', $imputables[0]->clave);
        $this->assertSame('horas_liquidadas', $imputables[12]->clave);
        $this->assertSame(
            ['fijos', 'horas_liquidadas'],
            array_values(array_map(static fn (RubroDelCatalogo $r): string => $r->clave, array_filter($imputables, static fn (RubroDelCatalogo $r): bool => $r->cargaPorPeriodo))),
        );

        $presupuestables = array_map(static fn (RubroDelCatalogo $r): string => $r->clave, $catalogo->presupuestables());
        $this->assertCount(12, $presupuestables);
        $this->assertNotContains('epp', $presupuestables);
    }

    public function test_epp_resume_en_insumos_y_los_demas_en_si_mismos(): void
    {
        $catalogo = $this->app->make(CatalogoDeRubros::class);

        $this->assertSame('insumos', $catalogo->resumeEn('epp'));
        $this->assertNull($catalogo->resumeEn('materiales'));
        $this->assertNull($catalogo->resumeEn('no_existe'));
    }

    public function test_hasta_que_gastos_cargue_la_suya_no_hay_gastos_y_una_obra_sin_lista_no_tiene_materiales(): void
    {
        $gastado = $this->app->make(GastadoDeObras::class);
        $this->assertSame([1 => [], 2 => []], $gastado->totalesPorRubro([1, 2]), 'Obra sin renglones: [] (§12.4.1).');
        $this->assertSame([], $gastado->renglonesDeObra(1, new FiltroRenglones));
        $this->assertSame([], $gastado->contarRenglonesPorMaterial([1]));

        $materiales = $this->app->make(MaterialesDeObra::class);
        $this->assertSame([], $materiales->opciones(1, null));
        $this->assertFalse($materiales->perteneceAObra(1, 1));
    }

    public function test_un_material_fuera_de_presupuesto_no_tiene_cantidad_presupuestada(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('materiales'));

        // Sin las FK, para que lo que falle sea la regla y no la obra o el usuario.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::table('materiales')->insert([
                'obra_id' => 999, 'nombre' => 'Cemento x 50 kg', 'nombre_clave' => 'cemento x 50 kg', 'unidad' => 'bolsa',
                'cantidad_presupuestada' => '10.000', 'fuera_de_presupuesto' => true, 'created_by' => 1, 'updated_by' => 1,
            ]);
            $this->fail('La base aceptó un material fuera de presupuesto con cantidad.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('materiales_fuera_sin_cantidad', $e->getMessage());
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
