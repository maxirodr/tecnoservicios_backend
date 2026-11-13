<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraContacto;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Str;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** HU-012 · F-16 · F-23 (W-06): alta de obra. */
final class AltaObraTest extends ObrasTestCase
{
    public function test_obra_nace_confirmada_aunque_manden_otro_estado(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['estado' => 'terminada']))
            ->assertCreated()
            ->assertJsonPath('data.estado', 'confirmada')
            ->assertJsonPath('data.numero_presupuesto', '2-131-2026')
            ->assertJsonPath('data.revision', 'REV01')
            ->assertJsonPath('data.presupuestos.0.es_principal', true)
            ->assertJsonPath('data.presupuestos.0.oferta.nombre', 'oferta-ejemplo.pdf')
            ->assertJsonPath('data.contactos.comprador.email', 'compras@planta-ejemplo.com');
    }

    public function test_alta_sin_oc_es_valida(): void
    {
        $laura = $this->laura();

        $this->crearObra($laura);

        $presupuesto = Presupuesto::query()->sole();
        $this->assertNull($presupuesto->aprobacion_tipo);
        $this->assertNull($presupuesto->aprobacion_numero);
    }

    public function test_alta_exige_numero_trabajo_cliente_responsable_oferta_y_contactos(): void
    {
        $laura = $this->laura();

        $this->actingAs($laura)->postJson('/api/obras', ['presupuesto' => ['numero' => ''], 'contactos' => ['solicitante' => ['nombre' => 'X']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre_trabajo', 'cliente', 'responsable_id', 'presupuesto.numero', 'presupuesto.oferta',
                'contactos.solicitante.email', 'contactos.solicitante.telefono', 'contactos.comprador.nombre',
            ]);

        $this->assertSame(0, Obra::query()->count());
    }

    public function test_revision_del_principal_es_obligatoria(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['presupuesto' => ['revision' => null]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto.revision']);
    }

    public function test_oferta_tecnica_acepta_archivo_o_link(): void
    {
        $laura = $this->laura();
        $link = $this->link($laura, 'presupuesto', 'https://drive.example/oferta-2-131-2026');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $link))
            ->assertCreated()
            ->assertJsonPath('data.presupuestos.0.oferta.tipo', 'link')
            ->assertJsonPath('data.presupuestos.0.oferta.descarga', null);
    }

    public function test_alta_crea_obra_presupuesto_contactos_y_adjuntos_en_una_transaccion(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');
        $oc = $this->link($laura, 'aprobacion');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, [
            'presupuesto' => ['aprobacion' => ['tipo' => 'oc', 'numero' => '4500-000123', 'adjunto' => $oc]],
            'adicionales' => [['numero' => '2-131-2026 a1', 'descripcion' => 'Reparación de juntas']],
        ]))->assertCreated()
            ->assertJsonPath('data.adicionales', 1)
            ->assertJsonPath('data.presupuestos.0.aprobacion.numero', '4500-000123')
            ->assertJsonPath('data.presupuestos.0.aprobacion.adjunto.tipo', 'link')
            ->assertJsonPath('data.presupuestos.1.numero', '2-131-2026 A1')
            ->assertJsonPath('data.presupuestos.1.revision', null);

        $this->assertSame(1, Obra::query()->count());
        $this->assertSame(2, Presupuesto::query()->count());
        $this->assertSame(2, ObraContacto::query()->count());
        $this->assertFalse($this->adjunto($oferta)->esTemporal());
        $this->assertFalse($this->adjunto($oc)->esTemporal());
    }

    public function test_alta_fallida_deja_los_adjuntos_temporales(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');
        $ajena = $this->subir($this->diego(), 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, [
            'adicionales' => [['numero' => '2-140-2026', 'oferta' => $ajena]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['adicionales.0.oferta' => 'Ese adjunto no se puede usar acá.']);

        $this->assertSame(0, Obra::query()->count());
        $this->assertSame(0, Presupuesto::query()->count());
        $this->assertTrue($this->adjunto($oferta)->esTemporal());
    }

    public function test_carpeta_drive_solo_acepta_link(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        foreach ([41, 'ftp://servidor-ejemplo/plano.pdf', 'javascript:alert(1)', 'drive.google.com/carpeta'] as $valor) {
            $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['carpeta_drive_url' => $valor]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['carpeta_drive_url']);
        }

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['carpeta_drive_url' => 'https://drive.example/obra']))
            ->assertCreated()
            ->assertJsonPath('data.carpeta_drive_url', 'https://drive.example/obra');
    }

    public function test_aprobacion_con_numero_sin_tipo_se_toma_como_oc(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['presupuesto' => ['aprobacion' => ['numero' => '4500-000123']]]))
            ->assertCreated()
            ->assertJsonPath('data.presupuestos.0.aprobacion.tipo', 'oc')
            ->assertJsonPath('data.presupuestos.0.aprobacion.etiqueta', 'Orden de compra');
    }

    public function test_aprobacion_con_tipo_exige_numero_o_adjunto(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['presupuesto' => ['aprobacion' => ['tipo' => 'contrato']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto.aprobacion.numero']);
    }

    public function test_numero_de_presupuesto_es_unico_en_todas_las_obras(): void
    {
        $laura = $this->laura();
        $existente = $this->crearObra($laura, null, ['presupuesto' => ['numero' => '2-031-2026']]);
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['presupuesto' => ['numero' => '2-31-2026', 'revision' => 'REV02']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto.numero' => "El presupuesto 2-031-2026 ya está en la obra 'Piso industrial nave 3'."])
            ->assertJsonPath('data.obra.id', $existente->id);
    }

    public function test_numero_se_normaliza_y_la_revision_escrita_en_el_numero_se_separa(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['presupuesto' => ['numero' => ' 2/131/2026 rev02', 'revision' => null]]))
            ->assertCreated()
            ->assertJsonPath('data.numero_presupuesto', '2-131-2026')
            ->assertJsonPath('data.revision', 'REV02');
    }

    public function test_numero_con_revision_distinta_a_la_cargada_da_422(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['presupuesto' => ['numero' => '2-131-2026 REV02']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto.numero' => 'El número trae REV02 pero la revisión dice REV01.']);
    }

    public function test_fechas_previstas_son_opcionales_y_fin_no_anterior_al_inicio(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['fecha_inicio_prevista' => '2026-11-20', 'fecha_fin_prevista' => '2026-11-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_fin_prevista' => 'La fecha de fin no puede ser anterior a la de inicio.']);

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['fecha_inicio_prevista' => '2026-11-20', 'fecha_fin_prevista' => '2026-12-10']))
            ->assertCreated()
            ->assertJsonPath('data.fecha_inicio_prevista', '2026-11-20')
            ->assertJsonPath('data.fecha_fin_prevista', '2026-12-10');
    }

    public function test_mail_de_contacto_mal_escrito_da_422(): void
    {
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta, ['contactos' => ['comprador' => ['email' => 'compras-arroba-ejemplo']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contactos.comprador.email']);
    }

    public function test_alta_con_oferta_de_otro_usuario_da_422(): void
    {
        $laura = $this->laura();
        $ajena = $this->subir($this->carla(), 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $ajena))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto.oferta' => 'Ese adjunto no se puede usar acá.']);
    }

    public function test_alta_con_idempotency_key_no_duplica(): void
    {
        $laura = $this->laura();
        $payload = $this->alta($laura, $this->subir($laura, 'presupuesto'));
        $clave = (string) Str::uuid();

        $primera = $this->actingAs($laura)->withHeader('Idempotency-Key', $clave)->postJson('/api/obras', $payload)->assertCreated();
        $segunda = $this->actingAs($laura)->withHeader('Idempotency-Key', $clave)->postJson('/api/obras', $payload)->assertCreated();

        $this->assertSame($primera->json('data.id'), $segunda->json('data.id'));
        $this->assertSame(1, Obra::query()->count());
    }

    public function test_solo_obras_editar_da_de_alta(): void
    {
        $carla = $this->carla();
        $oferta = $this->subir($carla, 'presupuesto');

        $this->actingAs($carla)->postJson('/api/obras', $this->alta($carla, $oferta))->assertForbidden();
        $this->actingAs($this->sinAcceso())->postJson('/api/obras', [])->assertForbidden();
        $this->assertSame(0, Obra::query()->count());
    }

    public function test_con_alcance_propias_solo_se_pone_a_si_mismo_como_responsable(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $laura = $this->laura();
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($this->diego(), $oferta))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['responsable_id']);
        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta))->assertCreated();
    }

    public function test_responsable_tiene_que_ser_un_usuario_activo(): void
    {
        $laura = $this->laura();
        $inactivo = $this->usuarioDe('Ingeniería de proyectos', ['activo' => false]);
        $oferta = $this->subir($laura, 'presupuesto');

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($inactivo, $oferta))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['responsable_id']);
    }

    public function test_alta_queda_registrada_con_quien_y_cuando(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $actividad = Actividad::query()->where('sujeto_type', Obra::MORPH)->where('sujeto_id', $obra->id)->sole();
        $this->assertSame('creado', $actividad->accion);
        $this->assertSame($laura->id, $actividad->user_id);
    }

    public function test_clientes_sugeridos_son_los_ya_usados(): void
    {
        $laura = $this->laura();
        $this->crearObra($laura);
        $this->crearObra($laura, null, ['cliente' => 'Terminal Sur SA', 'presupuesto' => ['numero' => '2-140-2026']]);

        $this->actingAs($laura)->getJson('/api/obras/clientes')
            ->assertOk()
            ->assertExactJson(['data' => ['Planta Ejemplo SA', 'Terminal Sur SA'], 'message' => null, 'errors' => null]);
        $this->actingAs($laura)->getJson('/api/obras/clientes?buscar=PLANTA ejemplo')->assertJsonPath('data', ['Planta Ejemplo SA']);
        $this->actingAs($this->sinAcceso())->getJson('/api/obras/clientes')->assertForbidden();
    }
}
