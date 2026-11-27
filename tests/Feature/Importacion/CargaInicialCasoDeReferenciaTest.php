<?php

declare(strict_types=1);

namespace Tests\Feature\Importacion;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Importacion\Support\ImportacionTestCase;
use Tests\Feature\Importacion\Support\PlantillasDelCasoDeReferencia;

/**
 * Test de aceptación de la semana 4 (`importacion.md` §13, backlog criterio global n.º 1): ensayo que no guarda nada,
 * confirmación, rendición al centavo, W-48, tareas sin avisos ni mails, y confirmar dos veces no duplica.
 */
final class CargaInicialCasoDeReferenciaTest extends ImportacionTestCase
{
    use PlantillasDelCasoDeReferencia;

    public function test_el_caso_de_referencia_se_ensaya_se_confirma_da_la_rendicion_al_centavo_y_no_duplica(): void
    {
        $this->prepararCasoDeReferencia();
        $antes = $this->conteos();

        [$codigo, $salida] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        $this->assertSame(0, $codigo, $salida);
        $this->assertStringContainsString('Ensayo: no se guardó nada. 3 obras: 2 entran, 1 con faltantes (2). Informe: ', $salida);
        $this->assertSame($antes, $this->conteos(), 'El ensayo no guarda nada');
        $this->informeDelEnsayoDeObras($this->informe($salida));

        [$codigo, $salida] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        $this->assertSame(0, $codigo, $salida);
        $this->assertStringContainsString('Carga confirmada (aprobó Carla). 2 obras guardadas, 0 ya estaban, 1 con faltantes. Informe: ', $salida);
        $despuesDeObras = $this->conteos();
        $this->assertSame([2, 3, 2, 3], [$despuesDeObras['obras'], $despuesDeObras['presupuestos'], $despuesDeObras['gastos'], $despuesDeObras['materiales']]);
        $this->rendicionAlCentavo();

        [, $salida] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        $this->assertStringContainsString('0 obras guardadas, 2 ya estaban, 1 con faltantes.', $salida);
        $this->assertSame($despuesDeObras, $this->conteos(), 'Confirmar dos veces no duplica');

        [, $salida] = $this->correr('importacion:tareas', ['archivo' => 'tareas.xlsx']);
        $this->assertStringContainsString('Ensayo: no se guardó nada. 8 filas: 3 tareas y 1 documento entran, 1 completada no se importa, 3 con faltantes. Informe: ', $salida);
        $this->assertSame($despuesDeObras, $this->conteos());
        $this->informeDelEnsayoDeTareas($this->informe($salida));

        [$codigo, $salida] = $this->correr('importacion:tareas', ['archivo' => 'tareas.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        $this->assertSame(0, $codigo, $salida);
        $this->assertStringContainsString('Carga confirmada (aprobó Carla). 3 tareas y 1 documento guardados, 0 ya estaban', $salida);
        $this->tareasImportadas();

        $conTareas = $this->conteos();
        [, $salida] = $this->correr('importacion:tareas', ['archivo' => 'tareas.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        $this->assertStringContainsString('0 tareas y 0 documentos guardados, 4 ya estaban', $salida);
        $this->assertSame($conTareas, $this->conteos(), 'Las tareas ya importadas no se duplican');
    }

    /**
     * @param  array<string, list<list<mixed>>>  $informe
     */
    private function informeDelEnsayoDeObras(array $informe): void
    {
        $faltantes = array_map(static fn (array $f): array => [$f[2], $f[4]], $informe['Faltantes']);
        $this->assertEqualsCanonicalizing([
            ['2-131-2026', 'Falta el mail del comprador.'],
            ['2-131-2026', "El rubro 'VARIOS' no tiene equivalencia aprobada en la hoja Rubros."],
        ], $faltantes);
        $this->assertSame('VARIOS', $informe['Rubros por revisar'][0][0]);
        $control = array_column(array_map(static fn (array $c): array => [$c[1], $c], $informe['Control por rubro']), 1, 0);
        $this->assertSame(['1-200-2026', 'INSUMOS', '125.500,50', 'INSUMO 80.000 + INSUMOS 45.500,50'], $control['INSUMOS']);
        $this->assertCount(5, $informe['Control por rubro']);
        $avisos = array_column($informe['Avisos'], 3, 2);
        $this->assertSame('La obra no tiene presupuesto por rubro: la rendición va a mostrar lo gastado sin compararlo por rubro.', $avisos['2-140-2026']);
    }

    private function rendicionAlCentavo(): void
    {
        $carla = $this->carla();
        $obra = (int) Presupuesto::query()->where('numero', '1-200-2026')->value('obra_id');
        $this->assertSame('en_ejecucion', Obra::query()->findOrFail($obra)->estado->value);
        $this->assertSame('confirmada', Obra::query()->whereKey(Presupuesto::query()->where('numero', '2-140-2026')->value('obra_id'))->firstOrFail()->estado->value);

        $data = $this->actingAs($carla)->getJson("/api/obras/{$obra}/rendicion")->assertOk()->json('data');
        $rubros = array_column($data['rubros'], 'gastado', 'clave');
        $this->assertSame(['1250000.00', '137500.50', '60000.00', '900000.00'], [$rubros['materiales'], $rubros['insumos'], $rubros['logistica'], $rubros['horas_liquidadas']]);
        $this->assertSame('2347500.50', $data['total']['gastado']);

        $control = $this->getJson("/api/obras/{$obra}/control-cantidad")->assertOk()->json('data');
        $this->assertSame(['fecha' => '2026-11-30', 'carga_inicial' => true, 'incluye_otras' => false], $control['columnas']['hasta']);
        $this->assertSame([], $control['columnas']['fechas']);
        $this->assertSame(
            [['Cemento x 50 kg', '96.000', '54.000'], ['Hormigón H-21', '12.000', '28.000'], ['Malla sima', '0.000', '30.000']],
            array_map(static fn (array $m): array => [$m['nombre'], $m['total_retirado'], $m['resta_llevar']], $control['materiales']),
        );

        $gastado = Gasto::query()->with('renglones')->where('origen', 'saldo_inicial')->orderBy('id')->firstOrFail();
        $this->assertCount(5, $gastado->renglones);
        $historial = $this->getJson("/api/gastos/{$gastado->id}")->assertOk()->assertJsonPath('data.puede.editar', false)->json('data.historial');
        $this->assertStringContainsString('Equivalencias aprobadas por Carla: INSUMO → INSUMOS, LOGÍSTICA → LOGISTICA.', json_encode($historial, JSON_UNESCAPED_UNICODE) ?: '');
    }

    /**
     * @param  array<string, list<list<mixed>>>  $informe
     */
    private function informeDelEnsayoDeTareas(array $informe): void
    {
        $faltantes = array_map(static fn (array $f): array => [$f[2], $f[4], $f[5]], $informe['Faltantes']);
        $this->assertEqualsCanonicalizing([
            ['1-200-2026 · fila 11', 'Falta la fecha de entrega.', 'el sector Compras'],
            ['1-200-2026 · fila 12', "No encontramos a 'Cari' en la hoja Personas.", 'Administración'],
            ['2-131-2026 · fila 4', "La obra 2-131-2026 no está en el sistema: cargala en la hoja Obras o pasá la tarea a 'Sin obra' nombrando la obra en la tarea.", 'el sector Ingeniería de proyectos'],
        ], $faltantes);
        $this->assertSame(['Cari', '13', 'Carla'], [$informe['Personas por revisar'][0][0], (string) $informe['Personas por revisar'][0][1], $informe['Personas por revisar'][0][2]]);
        $this->assertCount(3, $informe['Para completar por sector']);
    }

    private function tareasImportadas(): void
    {
        $this->assertSame(0, DB::table('avisos')->count(), 'Importar no avisa a nadie');
        Mail::assertNothingQueued();
        Mail::assertNothingSent();
        $this->assertSame(3, Pedido::query()->where('carga_inicial', true)->count());
        $this->assertSame(1, ObraDocumento::query()->where('nombre', 'Certificado N°1')->count());

        $fila8 = Pedido::query()->where('tarea', 'Solicitud de materiales N°3')->firstOrFail();
        $this->assertSame(['en_curso', 'prioritario', false, null, null], [$fila8->estado->value, $fila8->prioridad->value, $fila8->prioridad_manual, $fila8->solicitante_id, $fila8->fecha_pedido]);
        $fila9 = Pedido::query()->where('tarea', 'Alquiler de minipala')->firstOrFail();
        $this->assertSame(['bloqueada', 'esperando presupuesto', 'prioritario', false], [$fila9->estado->value, $fila9->motivo_bloqueo, $fila9->prioridad->value, $fila9->prioridad_manual]);
        $this->assertSame($this->diego()->id, $fila9->solicitante_id);

        $ids = array_column((array) $this->actingAs($this->diego())->getJson('/api/pedidos/mis-tareas')->assertOk()->json('data'), 'id');
        $servicio = Pedido::query()->where('tarea', 'Service camioneta')->firstOrFail();
        $this->assertNull($servicio->propietario_id);
        $this->assertContains($fila8->id, $ids);
        $this->assertContains($servicio->id, $ids);

        $this->getJson("/api/pedidos/{$fila8->id}")->assertOk()->assertJsonPath('data.carga_inicial', true);
        $this->getJson("/api/pedidos/{$fila8->id}/recorrido")->assertOk()->assertJsonPath('data.0.que', 'Carga inicial desde la minuta (aprobó Carla)');
        $this->assertTrue(Actividad::query()->where('sujeto_type', 'pedido')->where('sujeto_id', $fila8->id)->value('carga_inicial'));
    }

    /**
     * @return array<string, int>
     */
    private function conteos(): array
    {
        return [
            'obras' => Obra::query()->count(),
            'presupuestos' => Presupuesto::query()->count(),
            'gastos' => Gasto::query()->count(),
            'materiales' => Material::query()->count(),
            'pedidos' => Pedido::query()->count(),
            'documentos' => ObraDocumento::query()->count(),
            'actividades' => Actividad::query()->count(),
            'adjuntos' => DB::table('adjuntos')->count(),
        ];
    }
}
