<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Events\GastoAnulado;
use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Gastos\Support\GastosTestCase;
use Tests\Feature\Gastos\Support\PartesDeHoras;

/** DU-C02 · W-10 "Parte de horas": cargar una vez y que cada renglón vaya a su obra (gastos.md RN-GAS-51…56). */
final class ParteDeHorasTest extends GastosTestCase
{
    use PartesDeHoras;

    private Obra $nave;

    private Obra $losa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nave = $this->obra('Piso industrial nave 3');
        $this->losa = $this->obra('Reparación de losa tanque B');
    }

    public function test_parte_de_horas_reparte_cada_renglon_a_su_obra_en_horas_liquidadas(): void
    {
        Event::fake([GastoImputado::class]);

        $respuesta = $this->cargar($this->diego(), $this->parteDelCaso49($this->nave, $this->losa))->assertCreated()
            ->assertJsonPath('message', '✓ Parte de horas cargado. Ya se restó en la rendición de Piso industrial nave 3 y Reparación de losa tanque B.')
            ->assertJsonPath('data.origen', 'parte_horas')
            ->assertJsonPath('data.tipo_documento', 'sin_documento')
            ->assertJsonPath('data.documento', 'Parte de horas 1 al 15-oct')
            ->assertJsonPath('data.proveedor.es_generico', true)
            ->assertJsonPath('data.fecha', '2026-10-15')
            ->assertJsonPath('data.total', '581800.00')
            ->assertJsonCount(3, 'data.renglones');

        $renglones = $respuesta->json('data.renglones');
        $this->assertIsArray($renglones);
        $this->assertSame(['Juan Pérez', 'Juan Pérez', 'Pablo Gómez'], array_column($renglones, 'empleado'));
        $this->assertSame(['460200.00', '83200.00', '38400.00'], array_column($renglones, 'total'));
        $this->assertSame(['88.500', '16.000', '8.000'], array_column($renglones, 'cantidad'));
        $this->assertSame(['5200.00', '5200.00', '4800.00'], array_column($renglones, 'precio_unitario'));
        $this->assertSame(['horas_liquidadas', 'horas_liquidadas', 'horas_liquidadas'], array_column(array_column($renglones, 'rubro'), 'clave'));
        $this->assertSame(['2026-10-15', '2026-10-15', '2026-10-15'], array_column($renglones, 'fecha'));
        $this->assertSame([false, false, false], array_column($renglones, 'resumido'));
        $this->assertNull($renglones[2]['obra']);

        Event::assertDispatched(GastoImputado::class, fn (GastoImputado $e): bool => $e->obraIds === [$this->nave->id, $this->losa->id]);
    }

    public function test_parte_todo_sin_obra_lo_dice_la_confirmacion(): void
    {
        $this->cargar($this->diego(), $this->parte([$this->horas('Pablo Gómez', null, '8', '4800')]))->assertCreated()
            ->assertJsonPath('message', '✓ Parte de horas cargado. No va a ninguna obra (todo sin obra).');
    }

    public function test_parte_de_horas_exige_empleado_horas_y_valor_hora(): void
    {
        $this->cargar($this->diego(), $this->parte([
            $this->horas('  ', $this->nave, '8', '5200'),
            $this->horas('Juan Pérez', $this->nave, '0', '5200'),
            $this->horas('Juan Pérez', $this->nave, '8.125', '5200'),
            $this->horas('Juan Pérez', $this->nave, '8', '0'),
        ]))->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.empleado', 'Renglón 1: escribí el empleado.'))
            ->assertJsonPath('errors', $this->error('renglones.1.cantidad', 'Renglón 2: poné las horas.'))
            ->assertJsonPath('errors', $this->error('renglones.2.cantidad', 'Renglón 3: hasta 2 decimales en las horas.'))
            ->assertJsonPath('errors', $this->error('renglones.3.precio_unitario', 'Renglón 4: poné el valor hora.'));

        $this->assertSame(0, Gasto::query()->count());
    }

    public function test_parte_de_horas_habla_de_horas_y_valor_hora_en_los_errores_de_forma(): void
    {
        $this->cargar($this->diego(), $this->parte([
            $this->horas('Juan Pérez', $this->nave, '8.1255', '5200.001'),
            ['empleado' => 'Pablo Gómez', 'obra_id' => null],
        ]))->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.cantidad', 'Renglón 1: hasta 2 decimales en las horas.'))
            ->assertJsonPath('errors', $this->error('renglones.0.precio_unitario', 'Renglón 1: hasta 2 decimales en el valor hora.'))
            ->assertJsonPath('errors', $this->error('renglones.1.cantidad', 'Renglón 2: poné las horas.'))
            ->assertJsonPath('errors', $this->error('renglones.1.precio_unitario', 'Renglón 2: poné el valor hora.'));
    }

    public function test_parte_de_horas_exige_el_periodo(): void
    {
        $this->cargar($this->diego(), $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200')], ['periodo_desde' => null]))
            ->assertUnprocessable()->assertJsonPath('errors', $this->error('periodo_desde', 'Poné desde y hasta.'));
        $this->cargar($this->diego(), $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200')], ['periodo_desde' => '2026-10-20', 'periodo_hasta' => '2026-10-31']))
            ->assertUnprocessable()->assertJsonPath('errors', $this->error('periodo_desde', 'El período no puede empezar después de hoy.'));
        $this->cargar($this->diego(), $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200')], ['periodo_desde' => '2026-10-15', 'periodo_hasta' => '2026-10-01']))
            ->assertUnprocessable()->assertJsonPath('errors', $this->error('periodo_hasta', "El 'hasta' no puede ser anterior al 'desde'."));
    }

    public function test_parte_de_horas_va_siempre_a_horas_liquidadas_aunque_el_front_mande_otro_rubro(): void
    {
        $gasto = $this->cargado($this->diego(), $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200', ['rubro' => 'materiales'])]));

        $this->assertSame('horas_liquidadas', $this->app->make(Rubros::class)->porId($gasto->renglones->first()?->rubro_id)?->clave);
    }

    public function test_concepto_del_parte_es_el_de_la_cabecera_y_por_defecto_horas_del_periodo(): void
    {
        $porDefecto = $this->cargado($this->diego(), $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200', ['concepto' => 'otro'])]));
        $propio = $this->cargado($this->diego(), $this->parte([$this->horas('Juan Pérez', $this->losa, '8', '5200')], ['concepto' => 'Horas 1ª quincena']));

        $this->assertSame(['Horas 1 al 15-oct'], $porDefecto->renglones->pluck('concepto')->all());
        $this->assertSame(['Horas 1ª quincena'], $propio->renglones->pluck('concepto')->all());
    }

    public function test_fecha_efectiva_del_parte_es_el_hasta_aunque_sea_futura(): void
    {
        $gasto = $this->cargado($this->diego(), $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200')], ['periodo_hasta' => '2026-10-31']));

        $this->assertSame('2026-10-31', $gasto->fecha->toDateString());
        $this->assertSame('2026-10-31', $gasto->renglones->first()?->fecha->toDateString());
    }

    public function test_parte_superpuesto_con_horas_por_periodo_avisa_y_se_guarda_si_se_confirma(): void
    {
        $diego = $this->diego();
        $this->cargar($diego, ['tipo_documento' => 'sin_documento', 'origen' => 'monto_periodo', 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-15',
            'renglones' => [$this->renglon('Horas liquidadas 1 al 15-oct', '1', '1300000.00', $this->nave, 'horas_liquidadas')]])->assertCreated();
        $this->cargar($diego, ['tipo_documento' => 'sin_documento', 'origen' => 'monto_periodo', 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-31',
            'renglones' => [$this->renglon('Fijos 1 al 31-oct', '1', '260000.00', $this->losa, 'fijos')]])->assertCreated();
        $parte = $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200'), $this->horas('Juan Pérez', $this->losa, '8', '5200')], ['periodo_desde' => '2026-10-10', 'periodo_hasta' => '2026-10-25']);

        $this->cargar($diego, $parte)->assertUnprocessable()->assertJsonPath('errors.confirmar_superposicion', [
            'Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 del 1 al 15-oct (1.300.000) cargadas como monto por período. ¿Cargar igual?',
        ]);
        $this->assertSame(0, Gasto::query()->where('origen', 'parte_horas')->count());

        $this->cargar($diego, [...$parte, 'confirmar_superposicion' => true])->assertCreated();
        // FIJOS no es horas: Losa tanque B sola no avisa.
        $this->cargar($diego, $this->parte([$this->horas('Juan Pérez', $this->losa, '8', '5200')]))->assertCreated();
    }

    public function test_horas_por_periodo_superpuestas_con_un_parte_avisan_y_se_guardan_si_se_confirma(): void
    {
        $diego = $this->diego();
        $this->cargar($diego, $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200'), $this->horas('Ana Gómez', $this->losa, '4', '5000')],
            ['periodo_desde' => '2026-10-10', 'periodo_hasta' => '2026-10-25']))->assertCreated();
        $horas = ['tipo_documento' => 'sin_documento', 'origen' => 'monto_periodo', 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-15',
            'renglones' => [$this->renglon('Horas liquidadas 1 al 15-oct', '1', '1300000.00', $this->nave, 'horas_liquidadas')]];

        // Al revés de RN-GAS-55: solo cuenta lo del parte en esa obra (8 × 5.200), no lo de Losa tanque B.
        $this->cargar($diego, $horas)->assertUnprocessable()->assertJsonPath('errors.confirmar_superposicion', [
            'Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 en el parte de horas del 10 al 25-oct (41.600). ¿Cargar igual?',
        ]);
        $this->assertSame(0, Gasto::query()->where('origen', 'monto_periodo')->count());

        $this->cargar($diego, [...$horas, 'confirmar_superposicion' => true])->assertCreated();
        // Los FIJOS no son horas: no miran el parte.
        $this->cargar($diego, ['tipo_documento' => 'sin_documento', 'origen' => 'monto_periodo', 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-31',
            'renglones' => [$this->renglon('Fijos 1 al 31-oct', '1', '260000.00', $this->nave, 'fijos')]])->assertCreated();
    }

    public function test_parte_de_horas_pide_gastos_editar(): void
    {
        $this->celda('Compras', 'gastos', 'ver');

        $this->cargar($this->carla(), $this->parteDelCaso49($this->nave, $this->losa))->assertForbidden()
            ->assertJsonPath('message', 'Solo administración y compras cargan gastos.');
        $this->cargar($this->laura(), $this->parteDelCaso49($this->nave, $this->losa))->assertForbidden();
        $this->assertSame(0, Gasto::query()->count());
    }

    public function test_gastos_editar_solo_sus_obras_carga_el_parte_solo_en_sus_obras(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');
        $laura = $this->laura();
        $suya = $this->obra('Ampliación de nave de ensayo', $laura);

        $this->cargar($laura, $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200')]))->assertForbidden()
            ->assertJsonPath('message', 'Con tu permiso solo podés imputar a tus obras.');
        $this->cargar($laura, $this->parte([$this->horas('Juan Pérez', null, '8', '5200')]))->assertForbidden();
        $this->cargar($laura, $this->parte([$this->horas('Juan Pérez', $suya, '8', '5200')]))->assertCreated();
    }

    public function test_planilla_del_parte_es_una_sola(): void
    {
        $diego = $this->diego();
        $planillas = [$this->link($diego, 'comprobante_gasto', 'https://drive.example/horas-1.xlsx'), $this->link($diego, 'comprobante_gasto', 'https://drive.example/horas-2.xlsx')];

        $this->cargar($diego, $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200')], ['adjuntos' => $planillas]))->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('adjuntos', 'Son demasiados (máximo 1).'));
        $this->cargar($diego, $this->parte([$this->horas('Juan Pérez', $this->nave, '8', '5200')], ['adjuntos' => [$planillas[0]]]))->assertCreated()
            ->assertJsonCount(1, 'data.adjuntos');
    }

    public function test_sugiere_los_empleados_ya_cargados_el_mas_reciente_primero(): void
    {
        $diego = $this->diego();
        $this->cargar($diego, $this->parteDelCaso49($this->nave, $this->losa))->assertCreated();
        $this->cargar($diego, $this->parte([$this->horas('Juana Ruiz', $this->losa, '4', '5000')]))->assertCreated();

        $this->actingAs($diego)->getJson('/api/gastos/empleados')->assertOk()->assertExactJson([
            'data' => ['Juana Ruiz', 'Pablo Gómez', 'Juan Pérez'], 'message' => null, 'errors' => null,
        ]);
        $this->actingAs($diego)->getJson('/api/gastos/empleados?buscar=juan')->assertOk()->assertJsonPath('data', ['Juana Ruiz', 'Juan Pérez']);
    }

    public function test_sugerencias_no_repiten_por_tildes_y_usan_como_se_escribio_la_ultima_vez(): void
    {
        $diego = $this->diego();
        $this->cargar($diego, $this->parte([$this->horas('Juan Perez', $this->nave, '8', '5200')]))->assertCreated();
        $this->cargar($diego, $this->parte([$this->horas('Juan Pérez', $this->losa, '8', '5200')]))->assertCreated();

        $this->actingAs($diego)->getJson('/api/gastos/empleados?buscar=juan')->assertOk()->assertJsonPath('data', ['Juan Pérez']);
    }

    public function test_sugerencias_de_empleados_piden_gastos_editar_y_respetan_el_alcance(): void
    {
        $this->cargar($this->diego(), $this->parteDelCaso49($this->nave, $this->losa))->assertCreated();
        $laura = $this->laura();

        $this->actingAs($laura)->getJson('/api/gastos/empleados')->assertForbidden();
        $this->actingAs($this->marta())->getJson('/api/gastos/empleados')->assertForbidden();

        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');
        $this->nave->forceFill(['responsable_id' => $laura->id])->save();
        $this->actingAs($laura)->getJson('/api/gastos/empleados')->assertOk()->assertJsonPath('data', ['Juan Pérez']);
    }

    public function test_corregir_el_parte_nombra_al_empleado_en_el_historial(): void
    {
        $diego = $this->diego();
        $gasto = $this->cargado($diego, $this->parteDelCaso49($this->nave, $this->losa));
        $renglones = $gasto->renglones->map(static fn (GastoRenglon $r): array => [
            'id' => $r->id, 'empleado' => $r->empleado, 'obra_id' => $r->obra_id, 'cantidad' => $r->cantidad, 'precio_unitario' => $r->precio_unitario,
        ])->all();
        $renglones[0]['precio_unitario'] = '5500.00';
        $renglones[2]['empleado'] = 'Pablo A. Gómez';

        $this->actingAs($diego)->patchJson("/api/gastos/{$gasto->id}", ['concepto' => 'Horas 1ª quincena', 'renglones' => $renglones])->assertOk()
            ->assertJsonPath('data.renglones.0.total', '486750.00')
            ->assertJsonPath('data.renglones.1.concepto', 'Horas 1ª quincena')
            ->assertJsonPath('data.renglones.2.empleado', 'Pablo A. Gómez');

        $lineas = array_column($this->actingAs($diego)->getJson("/api/gastos/{$gasto->id}/historial")->assertOk()->json('data'), 'que');
        $this->assertSame([
            'Diego cargó el parte de horas (3 renglones)',
            'Diego cambió el concepto: Horas 1 al 15-oct → Horas 1ª quincena',
            "Diego cambió el valor hora de 'Juan Pérez': 5.200 → 5.500 (total 460.200 → 486.750)",
            "Diego cambió el empleado de 'Pablo Gómez': Pablo Gómez → Pablo A. Gómez",
        ], $lineas);
    }

    public function test_el_parte_se_anula_como_cualquier_gasto(): void
    {
        Event::fake([GastoAnulado::class]);
        $diego = $this->diego();
        $gasto = $this->cargado($diego, $this->parteDelCaso49($this->nave, $this->losa));

        $this->actingAs($diego)->postJson("/api/gastos/{$gasto->id}/anular", ['motivo' => 'Cargado dos veces'])->assertOk()
            ->assertJsonPath('message', 'Anulamos el parte de horas.');

        Event::assertDispatched(GastoAnulado::class, fn (GastoAnulado $e): bool => $e->obraIds === [$this->nave->id, $this->losa->id]);
    }
}
