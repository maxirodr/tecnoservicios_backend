<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use App\Modules\Gastos\Domain\Events\GastoCorregido;
use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Rendicion\Application\Rendicion\GastadoEnCache;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Rendicion\Support\RendicionTestCase;
use Tests\Feature\Rendicion\Support\Renglon;

/** F-49, F-50, F-54 (W-11) fuera del caso de referencia: rendicion.md §5 y casos borde de §10. */
final class RendicionDeObraTest extends RendicionTestCase
{
    public function test_obra_sin_presupuesto_muestra_lo_gastado_sin_rojos(): void
    {
        $obra = $this->obraDe($this->diego());
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '1000.00', 'materiales'));

        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")->assertOk()->json('data');

        $this->assertSame('sin_presupuesto', $data['presupuesto']['situacion']);
        $this->assertSame([null, '1000.00', null, null, false], [$data['rubros'][0]['presupuestado'], $data['rubros'][0]['gastado'], $data['rubros'][0]['saldo'], $data['rubros'][0]['porcentaje'], $data['rubros'][0]['pasado']]);
        $this->assertSame(['presupuestado' => null, 'gastado' => '1000.00', 'saldo' => null, 'porcentaje' => null, 'pasado' => false, 'rubros_pasados' => null], $data['total']);
        $this->assertNull($data['monto']);
    }

    public function test_muestra_los_doce_rubros_en_orden_aunque_no_haya_gastos(): void
    {
        $obra = $this->obraDe($this->diego());

        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")->assertOk()->json('data');

        $this->assertCount(12, $data['rubros']);
        $this->assertSame(['materiales', 'insumos', 'subcontratos'], array_slice(array_column($data['rubros'], 'clave'), 0, 3));
        $this->assertSame(['fijos', 'horas_liquidadas'], array_column(array_filter($data['rubros'], static fn (array $r): bool => $r['carga_por_periodo']), 'clave'));
        $this->assertSame([], $data['ultimos_gastos']);
    }

    public function test_solo_total_compara_total_contra_gastado_y_no_marca_rubros(): void
    {
        $obra = $this->obraDe($this->diego());
        $this->soloTotal($this->principal($obra), '3600000.00');
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '3700000.00', 'materiales'));

        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")->assertOk()->json('data');

        $this->assertSame('solo_total', $data['presupuesto']['situacion']);
        $this->assertSame([null, false], [$data['rubros'][0]['presupuestado'], $data['rubros'][0]['pasado']]);
        $this->assertSame('3600000.00', $data['sin_desglose']['presupuestado']);
        $this->assertSame(['3600000.00', '-100000.00', '102.78', true, null], [$data['total']['presupuestado'], $data['total']['saldo'], $data['total']['porcentaje'], $data['total']['pasado'], $data['total']['rubros_pasados']]);
    }

    public function test_presupuesto_sin_cargar_no_suma_y_avisa(): void
    {
        $obra = $this->obraDe($this->diego());
        $this->porRubro($this->principal($obra), ['materiales' => '1000.00']);
        $a2 = $this->adicional($obra, 'X-1-2026 A2');

        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")->assertOk()->json('data');

        $this->assertSame([$a2->numero], $data['presupuesto']['sin_cargar']);
        $this->assertSame('por_rubro', $data['presupuesto']['situacion']);
        $this->assertSame('1000.00', $data['total']['presupuestado']);
    }

    public function test_rubro_pasado_es_estricto_al_centavo(): void
    {
        $obra = $this->obraDe($this->diego());
        $this->porRubro($this->principal($obra), ['materiales' => '950000.00', 'insumos' => '950000.00']);
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Justo', '950000.00', 'materiales'));
        $this->gastado->agregar($obra->id, Renglon::de(2, 1, '2026-10-02', 'factura', 'Un centavo más', '950000.01', 'insumos'));

        $rubros = $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")->assertOk()->json('data.rubros');

        $this->assertSame(['100.00', '0.00', false], [$rubros[0]['porcentaje'], $rubros[0]['saldo'], $rubros[0]['pasado']]);
        $this->assertSame(['100.00', '-0.01', true], [$rubros[1]['porcentaje'], $rubros[1]['saldo'], $rubros[1]['pasado']]);
    }

    public function test_devolucion_resta_y_deja_el_porcentaje_negativo(): void
    {
        $obra = $this->obraDe($this->diego());
        $this->porRubro($this->principal($obra), ['materiales' => '100000.00']);
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-20', 'devolucion', 'Cemento (sobrante)', '-39402.00', 'materiales', ['cantidad' => '-4.000']));

        $fila = $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")->assertOk()->json('data.rubros.0');

        $this->assertSame(['-39402.00', '139402.00', '-39.40', false], [$fila['gastado'], $fila['saldo'], $fila['porcentaje'], $fila['pasado']]);
    }

    public function test_obra_terminada_tiene_rendicion(): void
    {
        $obra = $this->obraDe($this->diego(), ObraEstado::Terminada);
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'saldo_inicial', 'Carga inicial MATERIALES', '5000.00', 'materiales'));

        $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")
            ->assertOk()
            ->assertJsonPath('data.obra.estado', 'terminada')
            ->assertJsonPath('data.ultimos_gastos.0.documento', 'Carga inicial')
            ->assertJsonPath('data.total.gastado', '5000.00');
    }

    public function test_presupuesto_sin_monto_avisa_y_ganancia_negativa(): void
    {
        $obra = $this->obraDe($this->diego());
        $this->porRubro($this->principal($obra), ['materiales' => '1000000.00'], '1000000.00');
        $this->adicional($obra, 'X-1-2026 A1');
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '1250000.00', 'materiales'));

        $monto = $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}/rendicion")->assertOk()->json('data.monto');

        $this->assertSame(['X-1-2026 A1'], $monto['sin_monto']);
        $this->assertSame(['0.00', '0.00', '-250000.00', '-25.00'], [$monto['ganancia_prevista'], $monto['porcentaje_prevista'], $monto['ganancia_al_dia'], $monto['porcentaje_al_dia']]);
    }

    public function test_detalle_de_epp_solo_o_de_un_rubro_inexistente_devuelve_404_y_orden_invalido_422(): void
    {
        $obra = $this->obraDe($this->diego());
        $diego = $this->diego();

        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion/rubros/epp")->assertNotFound()->assertJsonPath('message', 'Ese rubro no tiene detalle propio.');
        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion/rubros/no_existe")->assertNotFound();
        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion/rubros/materiales?orden=nombre")->assertUnprocessable()->assertJsonValidationErrors(['orden']);
        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion/por-proveedor?rubro=epp")->assertUnprocessable()->assertJsonValidationErrors(['rubro']);
        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion/por-proveedor?desde=2026-11-30&hasta=2026-11-01")
            ->assertUnprocessable()->assertJsonPath('errors.hasta.0', "El 'hasta' no puede ser anterior al 'desde'.");
    }

    public function test_detalle_ordena_por_proveedor_y_por_total(): void
    {
        $obra = $this->obraDe($this->diego());
        $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-01', 'factura', 'A', '300.00', 'materiales', ['proveedor' => [20, 'Zeta Ejemplo']]));
        $this->gastado->agregar($obra->id, Renglon::de(2, 2, '2026-10-02', 'factura', 'B', '100.00', 'materiales', ['proveedor' => [21, 'Alfa Ejemplo']]));
        $this->gastado->agregar($obra->id, Renglon::de(3, 3, '2026-10-03', 'sin_comprobante', 'C', '200.00', 'materiales'));
        $url = "/api/obras/{$obra->id}/rendicion/rubros/materiales";

        $this->assertSame([2, 1, 3], array_column($this->actingAs($this->diego())->getJson("{$url}?orden=proveedor")->json('data.renglones'), 'renglon_id'));
        $this->assertSame([1, 3, 2], array_column($this->getJson("{$url}?orden=total")->json('data.renglones'), 'renglon_id'));
        $this->assertSame([2, 3, 1], array_column($this->getJson("{$url}?orden=total&dir=asc")->json('data.renglones'), 'renglon_id'));
    }

    public function test_gasto_imputado_invalida_la_rendicion_de_cada_obra(): void
    {
        [$a, $b] = [$this->obraDe($this->diego()), $this->obraDe($this->diego())];
        $diego = $this->diego();
        $this->actingAs($diego)->getJson("/api/obras/{$a->id}/rendicion")->assertJsonPath('data.total.gastado', '0.00');
        $this->assertTrue(Cache::has(app(GastadoEnCache::class)->clave($a->id)));

        $this->gastado->agregar($a->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '500.00', 'materiales'));
        $this->getJson("/api/obras/{$a->id}/rendicion")->assertJsonPath('data.total.gastado', '0.00');
        event(new GastoImputado(1, [$a->id, $b->id]));

        $this->getJson("/api/obras/{$a->id}/rendicion")->assertJsonPath('data.total.gastado', '500.00');
        $this->assertSame(2, $this->gastado->consultasDeTotales, 'Entre eventos, la rendición sale del cache.');
    }

    public function test_lectura_que_empezo_antes_del_gasto_no_deja_el_total_viejo_en_el_cache(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        // QC S3 M-15: la consulta lee los totales, en el medio se confirma un gasto (llega el evento) y recién
        // después la lectura guarda lo que había leído.
        $this->gastado->mientrasSeConsultan = function () use ($obra): void {
            $this->gastado->mientrasSeConsultan = null;
            $this->gastado->agregar($obra->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '500.00', 'materiales'));
            event(new GastoImputado(1, [$obra->id]));
        };

        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion")->assertJsonPath('data.total.gastado', '0.00');

        $this->getJson("/api/obras/{$obra->id}/rendicion")->assertJsonPath('data.total.gastado', '500.00');
    }

    public function test_gasto_corregido_invalida_obras_de_antes_y_despues(): void
    {
        [$a, $b] = [$this->obraDe($this->diego()), $this->obraDe($this->diego())];
        Cache::put(app(GastadoEnCache::class)->clave($a->id), ['materiales' => '1.00']);
        Cache::put(app(GastadoEnCache::class)->clave($b->id), ['materiales' => '2.00']);

        event(new GastoCorregido(7, [$a->id, $b->id]));

        $this->assertFalse(Cache::has(app(GastadoEnCache::class)->clave($a->id)));
        $this->assertFalse(Cache::has(app(GastadoEnCache::class)->clave($b->id)));
    }

    public function test_cambio_en_w13_invalida_el_cache_y_se_ve_en_la_proxima_lectura(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $principal = $this->principal($obra);
        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion")->assertOk();
        $version = Presupuesto::query()->findOrFail($principal->id)->updated_at->toIso8601String();

        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", ['modo' => 'por_rubro', 'montos' => ['materiales' => '5500000.00'], 'updated_at' => $version])->assertOk();

        $this->assertFalse(Cache::has(app(GastadoEnCache::class)->clave($obra->id)));
        $this->getJson("/api/obras/{$obra->id}/rendicion")->assertJsonPath('data.rubros.0.presupuestado', '5500000.00');
    }

    public function test_monto_no_se_cachea(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $this->porRubro($this->principal($obra), ['materiales' => '100.00'], '200.00');
        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/rendicion")->assertJsonPath('data.monto.monto_obra', '200.00');

        $this->principal($obra)->update(['monto_obra' => '300.00']);

        $this->getJson("/api/obras/{$obra->id}/rendicion")->assertJsonPath('data.monto.monto_obra', '300.00');
    }
}
