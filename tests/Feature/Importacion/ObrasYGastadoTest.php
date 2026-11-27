<?php

declare(strict_types=1);

namespace Tests\Feature\Importacion;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Rendicion\Domain\Models\Material;
use Tests\Feature\Importacion\Support\ImportacionTestCase;
use Tests\Feature\Importacion\Support\PlantillasDelCasoDeReferencia;

/** Obras, presupuestos, gastado y retirado (`importacion.md` §6.3…§6.5, RN-IMP-16…31). */
final class ObrasYGastadoTest extends ImportacionTestCase
{
    use PlantillasDelCasoDeReferencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carla();
        $this->laura();
    }

    /**
     * Ensayo y confirmación de un archivo con solo la obra 1-200-2026 (más los cambios del test).
     *
     * @param  callable(array<string, list<array<string, mixed>>>): array<string, list<array<string, mixed>>>  $cambiar
     * @return array{string, array<string, list<list<mixed>>>}
     */
    private function cargar(callable $cambiar, bool $confirmar = true, string $archivo = 'obras.xlsx'): array
    {
        $hojas = $this->hojasDeObras();
        $hojas['Obras'] = [$hojas['Obras'][0]];
        $hojas['Gastado'] = array_values(array_filter($hojas['Gastado'], static fn (array $g): bool => $g['Obra'] === '1-200-2026'));
        $this->xlsx($archivo, $cambiar($hojas), ['Presupuestos' => self::columnasDePresupuestos()]);
        [, $ensayo] = $this->correr('importacion:obras', ['archivo' => $archivo]);

        if (! $confirmar) {
            return [$ensayo, $this->informe($ensayo)];
        }

        [, $salida] = $this->correr('importacion:obras', ['archivo' => $archivo, '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);

        return [$salida, $this->informe($salida)];
    }

    /**
     * @param  array<string, list<list<mixed>>>  $informe
     * @return list<string>
     */
    private static function faltantes(array $informe): array
    {
        return array_map(static fn (array $f): string => (string) $f[4], $informe['Faltantes']);
    }

    public function test_obra_entra_en_ejecucion_con_historial(): void
    {
        $this->cargar(static fn (array $h): array => $h);
        $obra = Obra::query()->sole();

        $this->assertSame('en_ejecucion', $obra->estado->value);
        $historial = $this->actingAs($this->carla())->getJson("/api/obras/{$obra->id}/historial")->assertOk()->json('data');
        $textos = json_encode($historial, JSON_UNESCAPED_UNICODE) ?: '';
        $this->assertStringContainsString('Creó la obra (carga inicial)', $textos);
        $this->assertStringContainsString('Cambió el estado: Confirmada → En ejecución (carga inicial)', $textos);
    }

    public function test_obra_incompleta_no_carga_nada_y_lista_todos_sus_faltantes(): void
    {
        [$salida, $informe] = $this->cargar(static function (array $h): array {
            $h['Obras'][0]['Cliente'] = null;
            $h['Obras'][0]['Responsable de obra (mail)'] = 'nadie@ejemplo.com';
            $h['Obras'][0]['Oferta técnica (link)'] = 'drive.ejemplo.com/oferta';
            $h['Materiales'][0]['Unidad'] = null;

            return $h;
        });

        $this->assertStringContainsString('0 obras guardadas, 0 ya estaban, 1 con faltantes', $salida);
        $this->assertSame([0, 0, 0], [Obra::query()->count(), Gasto::query()->count(), Material::query()->count()]);
        $this->assertEqualsCanonicalizing([
            'Falta el cliente.',
            'No hay un usuario activo con el mail nadie@ejemplo.com.',
            'El link tiene que empezar con http:// o https://.',
            'Falta la unidad.',
        ], self::faltantes($informe));
    }

    public function test_obra_existente_no_se_toca(): void
    {
        $this->cargar(static fn (array $h): array => $h);
        $obra = Obra::query()->sole();
        Gasto::query()->get()->each(fn (Gasto $g) => $this->actingAs($this->carla())->postJson("/api/gastos/{$g->id}/anular", ['motivo' => 'Equivalencia equivocada'])->assertOk());

        [$salida, $informe] = $this->cargar(static function (array $h): array {
            $h['Obras'][0]['Trabajo'] = 'Otro nombre';

            return $h;
        }, true, 'otra.xlsx');

        $this->assertStringContainsString('1 obras guardadas, 0 ya estaban', $salida);
        $this->assertSame('Ampliación de nave de ensayo', $obra->refresh()->nombre_trabajo);
        $this->assertContains('La obra ya está en el sistema: sus datos y presupuestos no se tocan; si falta algo, se completa en la ficha y en W-13.', array_column($informe['Avisos'], 3));
        $this->assertSame(2, Gasto::query()->whereNull('anulado_at')->count(), 'Anulada, se puede volver a importar (RN-IMP-27)');
    }

    public function test_presupuesto_por_rubro_y_total_mezclado_es_faltante(): void
    {
        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Presupuestos'][0]['Total'] = '100';

            return $h;
        }, false);

        $this->assertContains('Un presupuesto se carga por rubro o con el total, no las dos cosas.', self::faltantes($informe));
    }

    public function test_obra_sin_presupuesto_entra_con_aviso(): void
    {
        [$salida, $informe] = $this->cargar(static function (array $h): array {
            $h['Presupuestos'] = [];

            return $h;
        });

        $this->assertStringContainsString('1 obras guardadas', $salida);
        $this->assertSame(1, Presupuesto::query()->count());
        $this->assertContains('La obra no tiene presupuesto por rubro: la rendición va a mostrar lo gastado sin compararlo por rubro.', array_column($informe['Avisos'], 3));
    }

    public function test_rubro_mal_escrito_queda_para_revision_y_la_tilde_tambien_pide_equivalencia(): void
    {
        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Rubros'] = [];

            return $h;
        }, false);

        $this->assertEqualsCanonicalizing([
            "El rubro 'INSUMO' no tiene equivalencia aprobada en la hoja Rubros.",
            "El rubro 'LOGÍSTICA' no tiene equivalencia aprobada en la hoja Rubros.",
        ], self::faltantes($informe));
        $sugeridos = array_column($informe['Rubros por revisar'], 3, 0);
        $this->assertSame(['INSUMO' => 'INSUMOS', 'LOGÍSTICA' => 'LOGISTICA'], $sugeridos);
    }

    public function test_equivalencia_no_aprobada_no_se_usa(): void
    {
        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Rubros'][0]['Aprobado'] = 'No';

            return $h;
        }, false);

        $this->assertSame(["La equivalencia de 'INSUMO' dice 'No': ese gastado no entra hasta que se decida su rubro."], self::faltantes($informe));
    }

    public function test_suma_distinta_del_total_de_su_planilla_es_faltante(): void
    {
        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Obras'][0]['Total gastado según su planilla'] = '2.437.500,50';

            return $h;
        }, false);

        $this->assertSame(['La suma por rubro (2.347.500,50) no da el total de su planilla (2.437.500,50): diferencia 90.000.'], self::faltantes($informe));
    }

    public function test_gastado_negativo_y_mas_de_dos_decimales_son_faltantes(): void
    {
        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Obras'][0]['Total gastado según su planilla'] = null;
            $h['Gastado'][0]['Gastado hasta el corte'] = '-5';
            $h['Gastado'][1]['Gastado hasta el corte'] = '12,345';

            return $h;
        }, false);

        $this->assertEqualsCanonicalizing(['El gastado no puede ser negativo.', "El importe '12,345' tiene más de 2 decimales."], self::faltantes($informe));
    }

    public function test_fecha_de_corte_vacia_o_futura_es_faltante(): void
    {
        [, $vacia] = $this->cargar(static function (array $h): array {
            $h['Obras'][0]['Fecha de corte'] = null;

            return $h;
        }, false);
        [, $futura] = $this->cargar(static function (array $h): array {
            $h['Obras'][0]['Fecha de corte'] = '01/12/2026';

            return $h;
        }, false, 'futura.xlsx');

        $this->assertSame(['Falta la fecha de corte.'], self::faltantes($vacia));
        $this->assertSame(['La fecha de corte no puede ser posterior a hoy.'], self::faltantes($futura));
    }

    public function test_renglones_hasta_el_corte_bloquean_por_doble_conteo_y_despues_no(): void
    {
        $this->cargar(static function (array $h): array {
            $h['Gastado'] = [];
            $h['Materiales'] = [];
            $h['Obras'][0]['Total gastado según su planilla'] = null;

            return $h;
        });
        $obra = Obra::query()->sole();
        $this->actingAs($this->carla())->postJson('/api/gastos', [
            'tipo_documento' => 'sin_documento', 'origen' => 'sin_comprobante', 'fecha' => '2026-11-25', 'rendido_por' => 'Carla',
            'proveedor_id' => null,
            'renglones' => [['fecha' => '2026-11-25', 'concepto' => 'Ticket ficticio', 'cantidad' => '1', 'precio_unitario' => '1000.00', 'obra_id' => $obra->id, 'rubro' => 'insumos']],
        ])->assertCreated();

        [, $informe] = $this->cargar(static fn (array $h): array => $h, false, 'con-gastado.xlsx');

        $this->assertContains('La obra ya tiene 1 renglones cargados con fecha hasta el 30/11/2026: se contarían dos veces. Anulalos o cargá el acumulado sin ellos.', self::faltantes($informe));

        [, $posterior] = $this->cargar(static function (array $h): array {
            $h['Obras'][0]['Fecha de corte'] = '24/11/2026';

            return $h;
        }, false, 'corte-anterior.xlsx');
        $this->assertSame([], self::faltantes($posterior));
    }

    public function test_carga_inicial_no_se_corrige_se_anula(): void
    {
        $this->cargar(static fn (array $h): array => $h);
        $gasto = Gasto::query()->where('origen', 'saldo_inicial')->orderBy('id')->firstOrFail();

        $this->actingAs($this->carla())->patchJson("/api/gastos/{$gasto->id}", ['updated_at' => $gasto->updated_at->toIso8601String()])
            ->assertStatus(422)
            ->assertJsonPath('message', 'La carga inicial no se corrige: anulala y volvé a importarla.');
        $this->getJson("/api/gastos/{$gasto->id}")->assertOk()
            ->assertJsonPath('data.puede.editar', false)
            ->assertJsonPath('data.puede.anular', true);
        $this->postJson("/api/gastos/{$gasto->id}/anular", ['motivo' => 'Equivalencia equivocada'])->assertOk();
    }

    public function test_carga_inicial_vigente_distinta_es_faltante(): void
    {
        $this->cargar(static fn (array $h): array => $h);

        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Gastado'][0]['Gastado hasta el corte'] = 1250001;
            $h['Obras'][0]['Total gastado según su planilla'] = null;

            return $h;
        }, false, 'cambiada.xlsx');

        $this->assertContains('La obra ya tiene su carga inicial de gastado (aprobó Carla el 30/11/2026). Para cargarla de nuevo, anulá esa primero.', self::faltantes($informe));
    }

    public function test_retirado_entra_con_importe_cero_y_no_suma_y_el_exceso_avisa(): void
    {
        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Materiales'][0]['Retirado hasta el corte'] = 160;

            return $h;
        });
        $retiro = Gasto::query()->with('renglones')->where('origen', 'saldo_inicial')->orderByDesc('id')->firstOrFail();

        $this->assertSame(['0.00', '0.00'], $retiro->renglones->map(fn ($r): string => (string) $r->total)->all());
        $this->assertContains('Cemento x 50 kg: retirado 160 de 150 presupuestadas (resta llevar −10).', array_column($informe['Avisos'], 3));
        $obra = Obra::query()->sole();
        $control = $this->actingAs($this->carla())->getJson("/api/obras/{$obra->id}/control-cantidad")->assertOk()->json('data');
        $this->assertSame('-10.000', $control['materiales'][0]['resta_llevar']);
    }

    public function test_material_existente_no_se_duplica(): void
    {
        $this->cargar(static fn (array $h): array => $h);

        [, $informe] = $this->cargar(static function (array $h): array {
            $h['Materiales'][0]['Cantidad presupuestada'] = 120;

            return $h;
        }, true, 'otra-vez.xlsx');

        $this->assertSame(3, Material::query()->count());
        $this->assertContains('Cemento x 50 kg ya está en la lista con 150: no se cambia.', array_column($informe['Avisos'], 3));
    }
}
