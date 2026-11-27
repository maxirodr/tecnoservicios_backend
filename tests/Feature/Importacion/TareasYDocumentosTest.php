<?php

declare(strict_types=1);

namespace Tests\Feature\Importacion;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Importacion\Support\ImportacionTestCase;
use Tests\Feature\Importacion\Support\PlantillasDelCasoDeReferencia;

/** Tareas abiertas y documentos de la minuta (`importacion.md` §6.6, §6.7, RN-IMP-32…39). */
final class TareasYDocumentosTest extends ImportacionTestCase
{
    use PlantillasDelCasoDeReferencia;

    private int $obraId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararCasoDeReferencia();
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        $this->obraId = (int) Obra::query()->where('nombre_trabajo', 'Ampliación de nave de ensayo')->value('id');
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @return array{string, array<string, list<list<mixed>>>}
     */
    private function tareas(array $filas, bool $confirmar = true): array
    {
        $hojas = $this->hojasDeTareas();
        $hojas['Tareas'] = $filas;
        $this->xlsx('pocas.xlsx', $hojas);
        [, $salida] = $this->correr('importacion:tareas', ['archivo' => 'pocas.xlsx']);

        if ($confirmar) {
            [, $salida] = $this->correr('importacion:tareas', ['archivo' => 'pocas.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        }

        return [$salida, $this->informe($salida)];
    }

    /**
     * @param  array<string, mixed>  $resto
     * @return array<string, mixed>
     */
    private function abierta(string $clave, array $resto): array
    {
        return $this->filaTarea($clave, 'Tarea', '1-200-2026', "Tarea {$clave}", ['Propietario' => 'Diego', 'Estado' => 'No iniciada', 'Fecha de entrega' => '04/12/2026', ...$resto]);
    }

    /**
     * @param  array<string, list<list<mixed>>>  $informe
     * @return list<string>
     */
    private static function faltantes(array $informe): array
    {
        return array_map(static fn (array $f): string => (string) $f[4], $informe['Faltantes']);
    }

    public function test_importar_minuta_trae_solo_pedidos_abiertos(): void
    {
        [$salida, $informe] = $this->tareas([
            $this->abierta('a', []), $this->abierta('b', ['Estado' => 'Completada']), $this->abierta('c', ['Estado' => null]),
        ]);

        $this->assertStringContainsString('1 tarea y 0 documentos guardados, 0 ya estaban, 1 completada no se importa, 1 con faltantes', $salida);
        $this->assertSame(['Poné el estado.'], self::faltantes($informe));
        $this->assertContains('Fila Completada: no se importa.', array_column($informe['Avisos'], 3));
        $this->assertSame(1, Pedido::query()->count());
    }

    public function test_sin_entrega_o_sin_propietario_queda_para_el_sector(): void
    {
        [, $informe] = $this->tareas([
            $this->abierta('a', ['Fecha de entrega' => null]),
            $this->abierta('b', ['Propietario' => null]),
        ], false);

        $this->assertSame(['Falta la fecha de entrega.', "Falta el propietario: una persona o '(en el sector)' con el sector."], self::faltantes($informe));
        $this->assertSame([['Compras', 'a'], ['Administración', 'b']], array_map(static fn (array $p): array => [$p[0], $p[1]], $informe['Para completar por sector']));
    }

    public function test_bloqueada_usa_notas_como_motivo_y_sin_notas_es_faltante(): void
    {
        [, $informe] = $this->tareas([
            $this->abierta('a', ['Estado' => 'Bloqueada', 'Notas' => 'Falta el remito']),
            $this->abierta('b', ['Estado' => 'Bloqueada']),
            $this->abierta('c', ['Notas' => 'Llamar antes']),
        ]);

        $this->assertSame(['Está Bloqueada sin motivo: escribí el motivo en Notas.'], self::faltantes($informe));
        $this->assertSame(['Falta el remito', null], [Pedido::query()->where('tarea', 'Tarea a')->value('motivo_bloqueo'), Pedido::query()->where('tarea', 'Tarea a')->value('aclaraciones')]);
        $this->assertSame('Llamar antes', Pedido::query()->where('tarea', 'Tarea c')->value('aclaraciones'));
    }

    public function test_en_curso_sin_propietario_es_faltante(): void
    {
        [, $informe] = $this->tareas([$this->abierta('a', ['Estado' => 'En curso', 'Propietario' => '(en el sector)', 'Sector' => 'Compras'])], false);

        $this->assertSame(['Una tarea En curso tiene que tener propietario: elegí quién la tiene.'], self::faltantes($informe));
    }

    public function test_prioridad_de_la_minuta_calculada_o_normal(): void
    {
        [, $informe] = $this->tareas([
            $this->abierta('vacia', ['Fecha de pedido' => '20/11/2026', 'Fecha de entrega' => '25/11/2026']),
            $this->abierta('p3', ['Prioridad' => 'P3']),
            $this->abierta('sin-pedida', ['Prioridad' => 'Prioritario']),
        ]);

        $this->assertSame(['prioritario', false], $this->prioridad('Tarea vacia'));
        $this->assertSame(['normal', false], $this->prioridad('Tarea p3'));
        $this->assertSame(['prioritario', false], $this->prioridad('Tarea sin-pedida'));
        $this->assertContains("Prioridad 'P3': queda Normal.", array_column($informe['Avisos'], 3));
    }

    public function test_prioridad_distinta_de_la_calculada_queda_manual(): void
    {
        $this->tareas([$this->abierta('a', ['Prioridad' => 'URGENTE', 'Fecha de pedido' => '20/11/2026', 'Fecha de entrega' => '05/12/2026'])]);

        $this->assertSame(['urgente', true], $this->prioridad('Tarea a'));
        $pedido = Pedido::query()->sole();
        $this->actingAs($this->carla())->getJson("/api/pedidos/{$pedido->id}")->assertOk()->assertJsonPath('data.prioridad_sugerida.prioridad', 'normal');
    }

    public function test_importar_no_genera_avisos_ni_mails(): void
    {
        $this->tareas([$this->abierta('a', ['Fecha de entrega' => '01/12/2026']), $this->abierta('b', ['Propietario' => 'Laura'])]);

        $this->assertSame(2, Pedido::query()->count());
        $this->assertSame(0, DB::table('avisos')->count());
        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    }

    public function test_tarea_de_obra_terminada_es_faltante(): void
    {
        Obra::query()->whereKey($this->obraId)->update(['estado' => 'terminada']);

        [, $informe] = $this->tareas([$this->abierta('a', [])], false);

        $this->assertSame(["La obra 1-200-2026 está Terminada: completá la tarea en la minuta o pasala a 'Sin obra' nombrando la obra en la tarea."], self::faltantes($informe));
    }

    public function test_propietario_sin_usuario_inactivo_o_sin_acceso_es_faltante_y_solicitante_no_encontrado_queda_vacio(): void
    {
        $this->persona('Pablo', 'pablo@ejemplo.com', 'Compras')->forceFill(['activo' => false])->save();

        [, $informe] = $this->tareas([
            $this->abierta('a', ['Propietario' => 'Cari']),
            $this->abierta('b', ['Propietario' => 'pablo@ejemplo.com']),
            $this->abierta('c', ['Solicitante' => 'Guido']),
            $this->abierta('d', ['Propietario' => 'diego@ejemplo.com', 'Solicitante' => 'Laura']),
        ]);

        $this->assertSame(["No encontramos a 'Cari' en la hoja Personas.", 'Pablo está inactiva: elegí a quién se la pasás.'], self::faltantes($informe));
        $this->assertContains("No encontramos a 'Guido' en Personas: queda sin solicitante.", array_column($informe['Avisos'], 3));
        $this->assertNull(Pedido::query()->where('tarea', 'Tarea c')->value('solicitante_id'));
        $this->assertSame($this->laura()->id, Pedido::query()->where('tarea', 'Tarea d')->value('solicitante_id'));
    }

    public function test_clave_repetida_es_faltante_en_todas_sus_filas(): void
    {
        [, $informe] = $this->tareas([$this->abierta('a', []), $this->abierta('a', [])], false);

        $this->assertSame(["La clave 'a' está repetida en las filas 2 y 3.", "La clave 'a' está repetida en las filas 2 y 3."], self::faltantes($informe));
    }

    public function test_documento_va_a_documentos_de_la_obra_con_su_historial(): void
    {
        $this->tareas([$this->filaTarea('doc', 'Documento', '1-200-2026', 'OC del cliente', [
            'Tipo de documento' => 'Otro', 'Links' => "https://drive.ejemplo.com/oc.pdf\nhttps://drive.ejemplo.com/oc-anexo.pdf",
        ])]);

        $documento = ObraDocumento::query()->with('adjuntos')->sole();
        $this->assertSame(['OC del cliente', 'otro', 2], [$documento->nombre, $documento->tipo->value, $documento->adjuntos->count()]);
        $historial = json_encode($this->actingAs($this->carla())->getJson("/api/obras/{$this->obraId}/historial")->json('data'), JSON_UNESCAPED_UNICODE) ?: '';
        $this->assertStringContainsString('OC del cliente (carga inicial)', $historial);
    }

    public function test_documento_sin_link_sin_fecha_o_sin_obra_es_faltante(): void
    {
        [, $informe] = $this->tareas([
            $this->filaTarea('a', 'Documento', '1-200-2026', 'OC del cliente', ['Tipo de documento' => 'Otro']),
            $this->filaTarea('b', 'Documento', '1-200-2026', 'Certificado N°2', ['Tipo de documento' => 'Certificado', 'Links' => 'https://drive.ejemplo.com/c2.pdf']),
            $this->filaTarea('c', 'Documento', 'Sin obra', 'Contrato', ['Tipo de documento' => 'Otro', 'Links' => 'https://drive.ejemplo.com/c.pdf']),
        ], false);

        $this->assertSame(['Un documento necesita al menos un link.', 'Un certificado necesita su fecha.', 'Un documento tiene que ser de una obra.'], self::faltantes($informe));
    }

    /**
     * @return array{string, bool}
     */
    private function prioridad(string $tarea): array
    {
        $pedido = Pedido::query()->where('tarea', $tarea)->firstOrFail();

        return [$pedido->prioridad->value, $pedido->prioridad_manual];
    }
}
