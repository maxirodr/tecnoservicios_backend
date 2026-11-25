<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use App\Modules\Rendicion\Domain\Models\Material;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Rendicion\Support\RendicionTestCase;
use Tests\Feature\Rendicion\Support\Renglon;

/** F-66 · HU-082, HU-083 (W-48): la lista de materiales y lo retirado (rendicion.md RN-REN-47…53, 61). */
final class ListaDeMaterialesTest extends RendicionTestCase
{
    public function test_lista_de_materiales_la_carga_control_cantidad_editar_y_queda_en_actividades(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);

        $respuesta = $this->actingAs($diego)->postJson("/api/obras/{$obra->id}/materiales", [
            'nombre' => '  Cemento x 50 kg ', 'unidad' => 'bolsa', 'cantidad_presupuestada' => '150', 'precio_presupuestado' => '9800',
        ])->assertCreated();

        $respuesta->assertJsonPath('message', 'Material agregado a la lista.')
            ->assertJsonPath('data.nombre', 'Cemento x 50 kg')
            ->assertJsonPath('data.cantidad_presupuestada', '150.000')
            ->assertJsonPath('data.precio_presupuestado', '9800.00')
            ->assertJsonPath('data.fuera_de_presupuesto', false)
            ->assertJsonPath('data.presupuesto.id', $this->principal($obra)->id);
        $actividad = Actividad::query()->where('sujeto_type', 'material')->sole();
        $this->assertSame(['material_agregado', $diego->id], [$actividad->accion, $actividad->user_id]);
    }

    public function test_nombre_de_material_no_se_repite_en_la_obra(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $this->material($obra, 'Cemento x 50 kg', 'bolsa', '150');

        $this->actingAs($diego)->postJson("/api/obras/{$obra->id}/materiales", ['nombre' => 'cemento  X 50 KG', 'unidad' => 'bolsa', 'cantidad_presupuestada' => '10'])
            ->assertUnprocessable()->assertJsonPath('errors.nombre.0', 'Ya está en la lista: Cemento x 50 kg.');
        $otra = $this->obraDe($diego);
        $this->actingAs($diego)->postJson("/api/obras/{$otra->id}/materiales", ['nombre' => 'cemento x 50 kg', 'unidad' => 'bolsa', 'cantidad_presupuestada' => '10'])->assertCreated();
    }

    public function test_valida_cantidad_precio_y_presupuesto_de_la_obra(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $ajeno = $this->principal($this->obraDe($diego));
        $url = "/api/obras/{$obra->id}/materiales";

        $this->actingAs($diego)->postJson($url, ['nombre' => 'Arena', 'unidad' => 'm³'])->assertUnprocessable()->assertJsonPath('errors.cantidad_presupuestada.0', 'Poné la cantidad presupuestada.');
        $this->actingAs($diego)->postJson($url, ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '1.2345'])->assertJsonPath('errors.cantidad_presupuestada.0', 'La cantidad lleva como máximo 3 decimales.');
        $this->actingAs($diego)->postJson($url, ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '-1'])->assertJsonPath('errors.cantidad_presupuestada.0', 'La cantidad no puede ser negativa.');
        $this->actingAs($diego)->postJson($url, ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => 'doce', 'precio_presupuestado' => 'mucho'])
            ->assertJsonPath('errors.cantidad_presupuestada.0', 'Escribí la cantidad como número, por ejemplo 12,5.')
            ->assertJsonPath('errors.precio_presupuestado.0', 'Escribí el precio como número, por ejemplo 12,5.');
        $this->actingAs($diego)->postJson($url, ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '1', 'precio_presupuestado' => '1.005'])->assertJsonPath('errors.precio_presupuestado.0', 'El precio lleva como máximo 2 decimales.');
        $this->actingAs($diego)->postJson($url, ['nombre' => '', 'unidad' => '', 'cantidad_presupuestada' => '1'])->assertJsonPath('errors.nombre.0', 'Escribí el material.')->assertJsonPath('errors.unidad.0', 'Escribí la unidad.');
        $this->actingAs($diego)->postJson($url, ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '1', 'presupuesto_id' => $ajeno->id])->assertJsonPath('errors.presupuesto_id.0', 'Ese presupuesto no es de esta obra.');
        $this->assertSame(0, Material::query()->count());
    }

    public function test_material_doble_envio_crea_uno_solo(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $clave = (string) Str::uuid();
        $datos = ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '5'];

        $primero = $this->actingAs($diego)->withHeader('Idempotency-Key', $clave)->postJson("/api/obras/{$obra->id}/materiales", $datos)->assertCreated();
        $segundo = $this->actingAs($diego)->withHeader('Idempotency-Key', $clave)->postJson("/api/obras/{$obra->id}/materiales", $datos)->assertCreated();

        $this->assertSame($primero->json('data.id'), $segundo->json('data.id'));
        $this->assertSame(1, Material::query()->count());
    }

    public function test_material_con_version_vieja_devuelve_409_y_la_correccion_queda_con_antes_y_despues(): void
    {
        $diego = $this->diego();
        $cemento = $this->material($this->obraDe($diego), 'Cemento x 50 kg', 'bolsa', '150');
        $version = $cemento->updated_at->toIso8601String();

        $this->travel(2)->seconds();
        $this->actingAs($diego)->patchJson("/api/materiales/{$cemento->id}", ['cantidad_presupuestada' => '160', 'updated_at' => $version])->assertOk()
            ->assertJsonPath('message', 'Material corregido.')->assertJsonPath('data.cantidad_presupuestada', '160.000');
        $this->actingAs($diego)->patchJson("/api/materiales/{$cemento->id}", ['cantidad_presupuestada' => '170', 'updated_at' => $version])
            ->assertStatus(409)->assertJsonPath('message', 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.');

        $actividad = Actividad::query()->where('sujeto_type', 'material')->where('accion', 'material_corregido')->sole();
        $this->assertSame(['cantidad_presupuestada' => ['antes' => '150.000', 'despues' => '160.000']], $actividad->cambios);
        $this->assertSame('160.000', $cemento->refresh()->cantidad_presupuestada);
    }

    public function test_completar_cantidad_pasa_a_la_lista_con_su_retirado(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $guantes = $this->material($obra, 'Guantes de trabajo', 'par', null);
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Guantes', '62000.00', 'epp', ['cantidad' => '20.000', 'material' => $guantes->id]));

        $this->actingAs($diego)->patchJson("/api/materiales/{$guantes->id}", ['cantidad_presupuestada' => '40'])->assertOk()->assertJsonPath('data.fuera_de_presupuesto', false);

        $control = $this->getJson("/api/obras/{$obra->id}/control-cantidad")->assertOk()->json('data');
        $this->assertSame([['Guantes de trabajo', '20.000', '20.000']], array_map(static fn (array $m): array => [$m['nombre'], $m['total_retirado'], $m['resta_llevar']], $control['materiales']));
        $this->assertSame([], $control['fuera_de_presupuesto']);
        $this->actingAs($diego)->patchJson("/api/materiales/{$guantes->id}", ['cantidad_presupuestada' => null])->assertJsonPath('errors.cantidad_presupuestada.0', 'Poné la cantidad presupuestada.');
    }

    public function test_no_se_quita_un_material_con_renglones_aunque_esten_anulados(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $cemento = $this->material($obra, 'Cemento x 50 kg', 'bolsa', '150');
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '100.00', 'materiales', ['material' => $cemento->id]));
        $this->gastado->agregar($obra->id, Renglon::de(2, 2, '2026-10-03', 'retiro_remito', 'Cemento', '100.00', 'materiales', ['material' => $cemento->id]), anulado: true);

        $this->actingAs($diego)->deleteJson("/api/materiales/{$cemento->id}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.material.0', 'Cemento x 50 kg ya tiene 2 retiros o pedidos: no se puede quitar. Corregí la cantidad presupuestada.');
        $this->assertNotNull(Material::query()->find($cemento->id));
    }

    public function test_quitar_bloquea_el_material_antes_de_contar_sus_renglones(): void
    {
        $diego = $this->diego();
        $malla = $this->material($this->obraDe($diego), 'Malla sima', 'paño', '30');
        DB::enableQueryLog();

        $this->actingAs($diego)->deleteJson("/api/materiales/{$malla->id}")->assertOk();

        $bloqueos = array_filter(DB::getQueryLog(), static fn (array $q): bool => str_contains($q['query'], 'from `materiales`') && str_contains($q['query'], 'for update'));
        $this->assertCount(1, $bloqueos, 'Un retiro simultáneo espera al bloqueo y se cuenta: 422 en lugar del 500 de la clave foránea.');
    }

    public function test_quitar_un_material_sin_renglones_queda_en_actividades(): void
    {
        $diego = $this->diego();
        $malla = $this->material($this->obraDe($diego), 'Malla sima', 'paño', '30');

        $this->actingAs($diego)->deleteJson("/api/materiales/{$malla->id}")->assertOk()->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Material quitado de la lista.');

        $this->assertNull(Material::query()->find($malla->id));
        $this->assertSame('material_quitado', Actividad::query()->where('sujeto_type', 'material')->where('sujeto_id', $malla->id)->value('accion'));
    }

    public function test_resta_llevar_puede_ser_negativa_y_las_columnas_son_seis_y_hasta(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $cemento = $this->material($obra, 'Cemento x 50 kg', 'bolsa', '150', '9800');
        foreach (range(1, 9) as $dia) {
            $this->gastado->agregar($obra->id, Renglon::de($dia, $dia, sprintf('2026-10-%02d', $dia), 'retiro_remito', 'Cemento', '1000.00', 'materiales', ['cantidad' => $dia === 9 ? '120.000' : '4.500', 'material' => $cemento->id]));
        }

        $data = $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/control-cantidad")->assertOk()->json('data');

        $this->assertSame(['2026-10-04', '2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09'], $data['columnas']['fechas']);
        $this->assertSame(['fecha' => '2026-10-03'], $data['columnas']['hasta']);
        $fila = $data['materiales'][0];
        $this->assertSame(['13.500', '156.000', '-6.000', '9000.00'], [$fila['retirado_hasta'], $fila['total_retirado'], $fila['resta_llevar'], $fila['total']]);
        $this->assertArrayNotHasKey('2026-10-01', $fila['retirado_por_fecha']);
        $this->assertSame('9000.00', $data['total_lista']);
    }

    public function test_obra_sin_lista_responde_vacia(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);

        $data = $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/control-cantidad")->assertOk()->json('data');

        $this->assertSame([[], [], '0.00', ['editar' => true, 'cargar_retiro' => true]], [$data['materiales'], $data['fuera_de_presupuesto'], $data['total_lista'], $data['puede']]);
        $this->assertSame(0, $this->gastado->consultasDeRenglones);
    }
}
