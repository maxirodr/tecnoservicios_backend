<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Actividad\Actividad;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** HU-014 (W-07, W-20) y HU-016 (W-23): ficha y edición con registro del cambio. */
final class FichaYEdicionTest extends ObrasTestCase
{
    public function test_ficha_informa_que_puede_hacer_el_usuario(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")
            ->assertOk()
            ->assertJsonPath('data.puede', [
                'editar' => true, 'cambiar_estado' => true, 'ver_montos' => true, 'cargar_montos' => false,
                'ver_monto_obra' => false, 'cargar_monto_obra' => false, 'ver_rendicion' => true, 'ver_pedidos' => true,
            ])
            ->assertJsonPath('data.created_by.nombre', 'Laura')
            ->assertJsonPath('data.contactos.solicitante.telefono', '291 000-0000');

        $this->actingAs($this->carla())->getJson("/api/obras/{$obra->id}")
            ->assertJsonPath('data.puede.editar', false)
            ->assertJsonPath('data.puede.cambiar_estado', false)
            ->assertJsonPath('data.puede.cargar_montos', true);
    }

    public function test_ficha_no_incluye_monto_obra_sin_permiso(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $sinMonto = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->assertOk();
        $this->assertArrayNotHasKey('monto_obra', (array) $sinMonto->json('data.presupuestos.0'));

        $this->actingAs($this->diego())->getJson("/api/obras/{$obra->id}")->assertJsonPath('data.presupuestos.0.monto_obra', null);
    }

    public function test_obra_inexistente_da_404_obra_no_encontrada(): void
    {
        $this->actingAs($this->carla())->getJson('/api/obras/999999')
            ->assertNotFound()
            ->assertExactJson(['data' => null, 'message' => 'Obra no encontrada.', 'errors' => null]);
    }

    public function test_obra_ajena_con_alcance_propias_da_404_como_si_no_existiera(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $laura = $this->laura();
        $ajena = $this->obraDe($this->diego());

        $this->actingAs($laura)->getJson("/api/obras/{$ajena->id}")->assertNotFound();
        $this->actingAs($laura)->patchJson("/api/obras/{$ajena->id}", ['nombre_trabajo' => 'X'])->assertNotFound();
        $this->actingAs($laura)->getJson("/api/obras/{$ajena->id}/historial")->assertNotFound();
        $this->actingAs($laura)->getJson("/api/obras/{$this->obraDe($laura)->id}")->assertOk();
    }

    public function test_sin_obras_ver_no_abre_la_ficha(): void
    {
        $obra = $this->obraDe($this->laura());

        $this->actingAs($this->sinAcceso())->getJson("/api/obras/{$obra->id}")->assertForbidden();
    }

    public function test_obra_terminada_se_consulta_completa(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $obra->forceFill(['estado' => ObraEstado::Terminada, 'terminada_at' => now(), 'terminada_por' => $laura->id])->save();

        $this->actingAs($this->carla())->getJson("/api/obras/{$obra->id}")
            ->assertOk()
            ->assertJsonPath('data.estado', 'terminada')
            ->assertJsonPath('data.terminada_por.nombre', 'Laura')
            ->assertJsonCount(1, 'data.presupuestos');
        $this->actingAs($this->carla())->getJson("/api/obras/{$obra->id}/historial")->assertOk();
    }

    public function test_editar_registra_antes_y_despues(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $hernan = $this->hernan();

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", [
            'nombre_trabajo' => 'Piso industrial nave 4',
            'responsable_id' => $hernan->id,
            'fecha_inicio_prevista' => '2026-11-20',
            'contactos' => ['comprador' => ['email' => 'compras2@planta-ejemplo.com']],
        ])->assertOk()
            ->assertJsonPath('data.nombre_trabajo', 'Piso industrial nave 4')
            ->assertJsonPath('data.responsable.nombre', 'Hernán')
            ->assertJsonPath('data.contactos.comprador.email', 'compras2@planta-ejemplo.com');

        $editado = Actividad::query()->where('sujeto_type', Obra::MORPH)->where('accion', 'editado')->sole();
        $this->assertSame([
            'responsable' => ['antes' => 'Laura', 'despues' => 'Hernán'],
            'nombre_trabajo' => ['antes' => 'Piso industrial nave 3', 'despues' => 'Piso industrial nave 4'],
            'fecha_inicio_prevista' => ['antes' => null, 'despues' => '2026-11-20'],
            'contacto_comprador.email' => ['antes' => 'compras@planta-ejemplo.com', 'despues' => 'compras2@planta-ejemplo.com'],
        ], $editado->cambios);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonPath('data.0.que', 'Responsable de obra: Laura → Hernán')
            ->assertJsonPath('data.0.quien.nombre', 'Laura')
            ->assertJsonPath('data.3.que', 'Contacto comprador: mail: compras@planta-ejemplo.com → compras2@planta-ejemplo.com')
            ->assertJsonPath('data.4.que', 'Oferta técnica: agregado (oferta-ejemplo.pdf)')
            ->assertJsonPath('data.5.que', 'Creó la obra');
    }

    public function test_guardar_sin_cambios_no_registra(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['nombre_trabajo' => 'Piso industrial nave 3', 'cliente' => 'Planta Ejemplo SA'])->assertOk();

        $this->assertSame(0, Actividad::query()->where('accion', 'editado')->count());
    }

    public function test_editar_no_cambia_el_estado(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['estado' => 'terminada', 'planta' => 'Nave 5'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'confirmada')
            ->assertJsonPath('data.planta', 'Nave 5');
    }

    public function test_obras_ver_no_puede_editar(): void
    {
        $obra = $this->obraDe($this->laura());

        $this->actingAs($this->carla())->patchJson("/api/obras/{$obra->id}", ['nombre_trabajo' => 'Otro'])->assertForbidden();
        $this->assertNotSame('Otro', $obra->refresh()->nombre_trabajo);
    }

    public function test_edicion_aplica_las_validaciones_del_alta(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['nombre_trabajo' => '', 'carpeta_drive_url' => 'ftp://servidor/x', 'contactos' => ['comprador' => ['email' => 'mal']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre_trabajo', 'carpeta_drive_url', 'contactos.comprador.email']);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['fecha_inicio_prevista' => '2026-11-20'])->assertOk();
        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['fecha_fin_prevista' => '2026-11-10'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_fin_prevista' => 'La fecha de fin no puede ser anterior a la de inicio.']);
    }

    public function test_responsable_inactivo_sigue_hasta_que_se_cambia_el_campo(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $laura->forceFill(['activo' => false])->save();
        $diego = $this->diego();

        $this->actingAs($diego)->patchJson("/api/obras/{$obra->id}", ['responsable_id' => $laura->id, 'planta' => 'Nave 9'])
            ->assertOk()
            ->assertJsonPath('data.responsable.activo', false);

        $otroInactivo = $this->usuarioDe('Compras', ['activo' => false]);
        $this->actingAs($diego)->patchJson("/api/obras/{$obra->id}", ['responsable_id' => $otroInactivo->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['responsable_id' => 'Elegí una persona activa.']);
    }

    public function test_obra_terminada_se_puede_editar(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura, ObraEstado::Terminada);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['carpeta_drive_url' => 'https://drive.example/obra'])
            ->assertOk()
            ->assertJsonPath('data.carpeta_drive_url', 'https://drive.example/obra');

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")->assertJsonPath('data.0.que', 'Carpeta de Drive: agregado');
    }
}
