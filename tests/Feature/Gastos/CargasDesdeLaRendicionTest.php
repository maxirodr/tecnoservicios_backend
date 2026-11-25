<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-47 · F-54 · HU-067…HU-070 · W-46: amortización, retiros, devoluciones y períodos (RN-GAS-22…28, §8.1). */
final class CargasDesdeLaRendicionTest extends GastosTestCase
{
    /**
     * @param  array<string, mixed>  $renglon
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function carga(string $origen, array $renglon, array $cambios = []): array
    {
        return ['tipo_documento' => 'sin_documento', 'origen' => $origen, 'fecha' => '2026-10-18', 'renglones' => [$renglon], ...$cambios];
    }

    /**
     * @return array<string, mixed>
     */
    private function amortizacion(Obra $obra, string $importe = '310000.00'): array
    {
        return $this->carga('amortizacion', $this->renglon('Amortización de herramientas de la obra', '1', $importe, $obra, 'amortizacion'));
    }

    public function test_amortizacion_una_por_obra(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();

        $this->cargar($diego, $this->amortizacion($nave))->assertCreated()
            ->assertJsonPath('message', '✓ Amortización cargada.')
            ->assertJsonPath('data.documento', 'Amortización')
            ->assertJsonPath('data.renglones.0.rubro.clave', 'amortizacion')
            ->assertJsonPath('data.proveedor.es_generico', true);

        $this->cargar($diego, $this->amortizacion($nave, '5000'))->assertUnprocessable()
            ->assertJsonPath('message', 'Esta obra ya tiene su amortización (310.000): corregila.');
        $this->cargar($diego, $this->amortizacion($this->obra('Reparación de losa tanque B')))->assertCreated();
    }

    public function test_dos_amortizaciones_simultaneas_dejan_una(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        DB::enableQueryLog();

        $this->cargar($this->diego(), $this->amortizacion($nave))->assertCreated();

        // La obra se bloquea antes de mirar si ya tiene su amortización: la segunda carga espera y la ve.
        $bloqueos = array_filter(DB::getQueryLog(), static fn (array $q): bool => str_contains($q['query'], 'from `obras`') && str_contains($q['query'], 'for update'));
        $this->assertNotEmpty($bloqueos);
    }

    public function test_amortizacion_exige_importe(): void
    {
        $this->cargar($this->diego(), $this->amortizacion($this->obra('Piso industrial nave 3'), '0'))->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.precio_unitario', 'Renglón 1: poné el importe.'));
    }

    public function test_amortizacion_y_devolucion_piden_rendicion_editar(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $this->celda('Compras', 'rendicion', 'ver');
        $carla = $this->carla();
        $devolucion = $this->carga('devolucion', $this->renglon('Cemento x 50 kg (sobrante)', '-4', '9800', $nave, 'materiales'));

        $this->cargar($carla, $this->amortizacion($nave))->assertForbidden()->assertJsonPath('message', 'No podés cargar en esta rendición.');
        $this->cargar($carla, $devolucion)->assertForbidden();
        $this->assertSame(0, Gasto::query()->count());
    }

    public function test_rendicion_editar_solo_sus_obras_carga_solo_en_sus_obras(): void
    {
        $this->celda('Ingeniería de proyectos', 'rendicion', 'editar', 'propias');
        $laura = $this->laura();
        $suya = $this->obra('Piso industrial nave 3', $laura);
        $ajena = $this->obra('Reparación de losa tanque B');

        $this->cargar($laura, $this->amortizacion($ajena))->assertForbidden()->assertJsonPath('message', 'No podés cargar en esta rendición.');
        $this->cargar($laura, $this->amortizacion($suya))->assertCreated();
    }

    public function test_retiro_exige_numero_de_remito_y_proveedor(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $endurecedor = $this->material($nave, 'Endurecedor superficial', 'bolsa', '18000.00');
        $datos = $this->carga('retiro_remito', $this->renglon('Endurecedor superficial (bolsa)', '10', '18000', $nave, 'materiales', ['material_id' => $endurecedor->id]));

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors.numero.0', 'Poné el número de remito.')
            ->assertJsonPath('errors.proveedor_id.0', 'Elegí el proveedor.');

        $this->cargar($this->diego(), [...$datos, 'numero' => '1-456', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id])
            ->assertCreated()
            ->assertJsonPath('message', '✓ Retiro cargado. Ya suma en la rendición y en el control por cantidad.')
            ->assertJsonPath('data.documento', 'Retiro · remito 0001-00000456')
            ->assertJsonPath('data.total', '180000.00');
    }

    public function test_retiro_con_material_de_otra_obra_es_rechazado(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $ajeno = $this->material($this->obra('Impermeabilización cubierta'), 'Cemento x 50 kg');
        $datos = $this->carga('retiro_remito', $this->renglon('Cemento', '10', '9800', $nave, 'materiales', ['material_id' => $ajeno->id]),
            ['numero' => '1-456', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id]);

        $this->cargar($this->diego(), $datos)->assertUnprocessable()->assertJsonPath('errors', $this->error(
            'renglones.0.material_id',
            'Renglón 1: el material Cemento x 50 kg es de otra obra: quitá el material o elegí uno de esta obra.',
        ));
    }

    public function test_retiro_exige_material_de_la_lista_o_no_esta_en_la_lista(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->carga('retiro_remito', $this->renglon('Arena', '2', '40000', $nave, 'materiales'),
            ['numero' => '1-456', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id]);

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.material_id', "Renglón 1: elegí el material o 'No está en la lista'."));
    }

    public function test_retiro_de_lo_que_no_esta_en_la_lista_crea_material_fuera_de_presupuesto_con_su_unidad(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->carga('retiro_remito', $this->renglon('Guantes y antiparras', '12', '4500', $nave, 'epp', [
            'material_nuevo' => ['nombre' => 'Guantes y antiparras', 'unidad' => 'par'],
        ]), ['numero' => '1-456', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id]);

        $this->cargar($this->diego(), $datos)->assertCreated()
            ->assertJsonPath('data.renglones.0.material.nombre', 'Guantes y antiparras')
            ->assertJsonPath('data.renglones.0.material.unidad', 'par');
        $this->assertSame([['obra' => $nave->id, 'nombre' => 'Guantes y antiparras', 'unidad' => 'par']], $this->materiales->creados);
    }

    public function test_remito_repetido_avisa_y_se_guarda_si_se_confirma(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $cemento = $this->material($nave, 'Cemento x 50 kg');
        $datos = $this->carga('retiro_remito', $this->renglon('Cemento', '10', '9800', $nave, 'materiales', ['material_id' => $cemento->id]),
            ['numero' => '1-456', 'fecha' => '2026-10-14', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id]);
        $diego = $this->diego();
        $this->cargado($diego, $datos);

        $this->cargar($diego, $datos)->assertUnprocessable()
            ->assertJsonPath('errors.confirmar_remito_repetido.0', 'Ese remito ya está cargado (14-oct, Piso industrial nave 3). ¿Cargarlo igual?');
        $this->cargar($diego, [...$datos, 'confirmar_remito_repetido' => true])->assertCreated();
    }

    public function test_retiro_con_control_cantidad_editar_sin_rendicion_editar_se_acepta(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $cemento = $this->material($nave, 'Cemento x 50 kg');
        $this->celda('Compras', 'rendicion', 'ver');
        $this->celda('Compras', 'gastos', 'ver');
        $datos = $this->carga('retiro_remito', $this->renglon('Cemento', '10', '9800', $nave, 'materiales', ['material_id' => $cemento->id]),
            ['numero' => '1-456', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id]);

        $this->cargar($this->carla(), $datos)->assertCreated();
        $this->celda('Compras', 'control_cantidad', 'ver');
        $this->cargar($this->persona('Carla B', 'Compras'), [...$datos, 'numero' => '1-457'])->assertForbidden();
    }

    public function test_devolucion_exige_cantidad_negativa(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->carga('devolucion', $this->renglon('Cemento', '4', '9800', $nave, 'materiales')))
            ->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.cantidad', 'Renglón 1: en una devolución la cantidad va en negativo.'));
    }

    public function test_devolucion_resta_en_el_gastado(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $cemento = $this->material($nave, 'Cemento x 50 kg');

        $this->cargar($this->diego(), $this->carga('devolucion', $this->renglon('Cemento x 50 kg (sobrante)', '-4', '9800', $nave, 'materiales', ['material_id' => $cemento->id])))
            ->assertCreated()
            ->assertJsonPath('message', '✓ Devolución cargada. Ya resta en la rendición.')
            ->assertJsonPath('data.total', '-39200.00')
            ->assertJsonPath('data.renglones.0.cantidad', '-4.000')
            ->assertJsonPath('data.proveedor.es_generico', true);
    }

    public function test_corregir_un_retiro_pide_gastos_editar(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $cemento = $this->material($nave, 'Cemento x 50 kg');
        $this->celda('Compras', 'gastos', 'ver');
        $carla = $this->carla();
        $retiro = $this->cargado($carla, $this->carga('retiro_remito', $this->renglon('Cemento', '10', '9800', $nave, 'materiales', ['material_id' => $cemento->id]),
            ['numero' => '1-456', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id]));

        $this->actingAs($carla)->patchJson("/api/gastos/{$retiro->id}", ['numero' => '1-457'])->assertForbidden();
        $this->actingAs($this->diego())->patchJson("/api/gastos/{$retiro->id}", ['numero' => '1-457'])->assertOk()
            ->assertJsonPath('data.numero', '0001-00000457');
    }

    public function test_monto_por_periodo_exige_desde_y_hasta(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $periodo = $this->carga('monto_periodo', $this->renglon('Horas liquidadas 1 al 15-oct', '1', '1300000', $nave, 'horas_liquidadas'));

        $this->cargar($this->diego(), $periodo)->assertUnprocessable()->assertJsonPath('errors.periodo_desde.0', 'Poné desde y hasta.');
        $this->cargar($this->diego(), [...$periodo, 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-09-30'])
            ->assertUnprocessable()->assertJsonPath('errors.periodo_hasta.0', "El 'hasta' no puede ser anterior al 'desde'.");
        $periodo['renglones'][0]['precio_unitario'] = '0';
        $this->cargar($this->diego(), [...$periodo, 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-15'])
            ->assertUnprocessable()->assertJsonPath('errors', $this->error('renglones.0.precio_unitario', 'Renglón 1: poné el importe del período.'));
    }

    public function test_monto_por_periodo_solo_fijos_u_horas(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $periodo = $this->carga('monto_periodo', $this->renglon('Fijos', '1', '260000', $nave, 'materiales'), ['periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-31']);

        $this->cargar($this->diego(), $periodo)->assertUnprocessable()->assertJsonPath('errors', $this->error('renglones.0.rubro', 'Renglón 1: elegí el rubro.'));
    }

    public function test_monto_por_periodo_tiene_un_solo_renglon(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $renglon = $this->renglon('Horas', '1', '1300000', $nave, 'horas_liquidadas');

        $this->cargar($this->diego(), ['origen' => 'monto_periodo', 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-15', 'renglones' => [$renglon, $renglon]])
            ->assertUnprocessable()->assertJsonPath('errors.renglones.0', 'Esta carga lleva un solo renglón.');
    }

    public function test_fecha_del_periodo_es_el_hasta_aunque_sea_futura(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $periodo = $this->carga('monto_periodo', $this->renglon('Fijos 1 al 31-oct', '1', '260000', $nave, 'fijos'), ['periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-31']);

        $this->cargar($this->diego(), $periodo)->assertCreated()
            ->assertJsonPath('message', '✓ Período cargado. Ya resta en la rendición.')
            ->assertJsonPath('data.fecha', '2026-10-31')
            ->assertJsonPath('data.renglones.0.fecha', '2026-10-31')
            ->assertJsonPath('data.documento', 'Período 1 al 31-oct');
    }

    public function test_desde_posterior_a_hoy_es_rechazado(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $periodo = $this->carga('monto_periodo', $this->renglon('Fijos', '1', '260000', $nave, 'fijos'), ['periodo_desde' => '2026-10-20', 'periodo_hasta' => '2026-10-31']);

        $this->cargar($this->diego(), $periodo)->assertUnprocessable()->assertJsonPath('errors.periodo_desde.0', 'El período no puede empezar después de hoy.');
    }

    public function test_superposicion_avisa_y_se_guarda_si_se_confirma(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $this->cargado($diego, $this->carga('monto_periodo', $this->renglon('Horas liquidadas 1 al 15-oct', '1', '1300000', $nave, 'horas_liquidadas'),
            ['periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-15']));
        $segunda = $this->carga('monto_periodo', $this->renglon('Horas liquidadas 10 al 25-oct', '1', '900000', $nave, 'horas_liquidadas'),
            ['periodo_desde' => '2026-10-10', 'periodo_hasta' => '2026-10-25']);

        $this->cargar($diego, $segunda)->assertUnprocessable()->assertJsonPath(
            'errors.confirmar_superposicion.0',
            'Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 del 1 al 15-oct (1.300.000). ¿Cargar igual?',
        );
        $this->cargar($diego, [...$segunda, 'confirmar_superposicion' => true])->assertCreated();
    }

    public function test_periodo_pide_rendicion_editar(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->marta(), $this->carga('monto_periodo', $this->renglon('Fijos', '1', '260000', $nave, 'fijos'), ['periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-31']))
            ->assertForbidden()->assertJsonPath('message', 'No podés cargar en esta rendición.');
    }

    public function test_las_cargas_de_la_rendicion_van_todas_a_su_obra(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $losa = $this->obra('Reparación de losa tanque B');
        $datos = ['origen' => 'devolucion', 'fecha' => '2026-10-18', 'renglones' => [
            $this->renglon('Cemento', '-4', '9800', $nave, 'materiales'),
            $this->renglon('Arena', '-1', '40000', $losa, 'materiales'),
        ]];

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.1.obra_id', 'Renglón 2: todos los renglones van a la obra de la rendición.'));
    }

    public function test_renglones_sin_documento_de_la_obra(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $this->cargado($diego, $this->amortizacion($nave));
        $this->cargado($diego, $this->carga('devolucion', $this->renglon('Cemento', '-4', '9800', $nave, 'materiales')));

        $this->actingAs($diego)->getJson("/api/obras/{$nave->id}/renglones-sin-documento")->assertOk()
            ->assertJsonPath('data.obra.nombre_trabajo', 'Piso industrial nave 3')
            ->assertJsonPath('data.amortizacion.total', '310000.00')
            ->assertJsonCount(1, 'data.devoluciones')
            ->assertJsonCount(0, 'data.retiros')
            ->assertJsonPath('data.puede', ['amortizacion' => true, 'retiros' => true, 'devoluciones' => true, 'periodos' => true]);

        $sofia = $this->sofia();
        $this->actingAs($sofia)->getJson("/api/obras/{$nave->id}/renglones-sin-documento")->assertForbidden()
            ->assertJsonPath('message', 'No podés cargar en esta rendición.');
        $this->actingAs($sofia)->getJson('/api/obras/999999/renglones-sin-documento')->assertForbidden();
        $this->actingAs($this->laura())->getJson("/api/obras/{$nave->id}/renglones-sin-documento")->assertForbidden()
            ->assertJsonPath('message', 'No podés cargar en esta rendición.');
        $this->actingAs($diego)->getJson('/api/obras/999999/renglones-sin-documento')->assertNotFound()
            ->assertJsonPath('message', 'No encontramos esta obra.');
    }

    public function test_con_control_cantidad_o_rendicion_editar_elige_el_proveedor_del_retiro_sin_proveedores_ver(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $cemento = $this->material($nave, 'Cemento x 50 kg');
        $tecnobahia = $this->proveedor('Tecnobahía Ejemplo');
        $sofia = $this->sofia();

        $this->actingAs($sofia)->getJson('/api/proveedores/opciones?buscar=tecno')->assertForbidden();

        foreach (['control_cantidad', 'rendicion'] as $i => $celda) {
            $this->celda('Seguridad e Higiene', $celda, 'editar');
            $quien = $this->persona("Sofía {$i}", 'Seguridad e Higiene');

            $this->actingAs($quien)->getJson('/api/proveedores/opciones?buscar=tecno')->assertOk()
                ->assertJsonPath('data.0.id', $tecnobahia->id);
            $this->cargar($quien, $this->carga('retiro_remito', $this->renglon('Cemento', '10', '9800', $nave, 'materiales', ['material_id' => $cemento->id]),
                ['numero' => "1-45{$i}", 'proveedor_id' => $tecnobahia->id]))->assertCreated();
            $this->celda('Seguridad e Higiene', $celda, 'sin_acceso');
        }
    }
}
