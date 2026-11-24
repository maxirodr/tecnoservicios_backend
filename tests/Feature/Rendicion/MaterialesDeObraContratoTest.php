<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use App\Modules\Obras\Application\Ports\MaterialesDePresupuesto;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Contracts\MaterialOpcion;
use App\Modules\Rendicion\Domain\Models\Material;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Rendicion\Support\RendicionTestCase;
use Tests\Feature\Rendicion\Support\Renglon;

/** Lo que Rendicion expone a Gastos y a Obras (rendicion.md §12.4.2 y §12.4.3, HU-086 c3 y c4). */
final class MaterialesDeObraContratoTest extends RendicionTestCase
{
    public function test_crear_fuera_de_presupuesto_reusa_el_mismo_nombre(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $materiales = $this->app->make(MaterialesDeObra::class);

        $id = $materiales->crearFueraDePresupuesto($obra->id, ' Guantes y antiparras ', 'par', $diego->id);

        $this->assertSame($id, $materiales->crearFueraDePresupuesto($obra->id, 'guantes  Y ANTIPARRAS', 'par', $diego->id));
        $material = Material::query()->findOrFail($id);
        $this->assertSame(['Guantes y antiparras', 'par', true, null], [$material->nombre, $material->unidad, $material->fuera_de_presupuesto, $material->cantidad_presupuestada]);
        $this->assertSame(1, Material::query()->count());
    }

    public function test_crear_fuera_de_presupuesto_exige_unidad(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);

        try {
            $this->app->make(MaterialesDeObra::class)->crearFueraDePresupuesto($obra->id, 'Guantes', ' ', $diego->id);
            $this->fail('Aceptó un material sin unidad.');
        } catch (ValidationException $e) {
            $this->assertSame(['unidad' => ['Escribí la unidad.']], $e->errors());
        }
    }

    public function test_pertenece_a_obra(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $cemento = $this->material($obra, 'Cemento x 50 kg', 'bolsa', '150');
        $materiales = $this->app->make(MaterialesDeObra::class);

        $this->assertTrue($materiales->perteneceAObra($cemento->id, $obra->id));
        $this->assertFalse($materiales->perteneceAObra($cemento->id, $this->obraDe($diego)->id));
        $this->assertFalse($materiales->perteneceAObra(999999, $obra->id));
    }

    public function test_opciones_de_materiales_traen_la_lista_primero_con_resta_y_precio(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $cemento = $this->material($obra, 'Cemento x 50 kg', 'bolsa', '150', '9800');
        $this->material($obra, 'Aditivo', 'litro', null);
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'retiro_remito', 'Cemento', '1000.00', 'materiales', ['cantidad' => '100.000', 'material' => $cemento->id]));

        $opciones = $this->app->make(MaterialesDeObra::class)->opciones($obra->id, null);

        $this->assertSame(
            [['Cemento x 50 kg', false, '50.000', '9800.00'], ['Aditivo', true, null, null]],
            array_map(static fn (MaterialOpcion $o): array => [$o->nombre, $o->fueraDePresupuesto, $o->restaLlevar, $o->precioPresupuestado], $opciones),
        );
        $this->assertSame(['Cemento x 50 kg'], array_map(static fn (MaterialOpcion $o): string => $o->nombre, $this->app->make(MaterialesDeObra::class)->opciones($obra->id, 'CEMENTO')));
        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/materiales/opciones?buscar=cem")
            ->assertOk()
            ->assertJsonPath('data.0', ['id' => $cemento->id, 'nombre' => 'Cemento x 50 kg', 'unidad' => 'bolsa', 'fuera_de_presupuesto' => false, 'resta_llevar' => '50.000', 'precio_presupuestado' => '9800.00']);
    }

    public function test_materiales_de_presupuesto_dice_si_un_presupuesto_tiene_materiales(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $a1 = $this->adicional($obra, 'X-1-2026 A1');
        $this->material($obra, 'Cemento x 50 kg', 'bolsa', '150', null, $a1);
        $puerto = $this->app->make(MaterialesDePresupuesto::class);

        $this->assertTrue($puerto->tieneMateriales($a1->id));
        $this->assertFalse($puerto->tieneMateriales($this->principal($obra)->id));
    }
}
