<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Permisos\CambiarCeldasAction;
use App\Modules\Identidad\Application\Usuarios\GuardaAdministradores;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;

/** F-60 · W-15 · HU-008 · HU-009 (RN-IDE-34…38). */
final class TablaDePermisosTest extends IdentidadTestCase
{
    public function test_ver_la_tabla_exige_usuarios_permisos_ver(): void
    {
        $this->actingAs($this->usuarioDe('Dirección'))->getJson('/api/permisos')
            ->assertOk()
            ->assertJsonCount(12, 'data.funciones')
            ->assertJsonCount(11, 'data.sectores')
            ->assertJsonCount(132, 'data.celdas')
            ->assertJsonPath('data.funciones.7', ['clave' => 'rendicion', 'nombre' => 'Rendición', 'admite_editar' => true, 'admite_alcance' => true, 'orden' => 8]);

        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/permisos')->assertForbidden();
    }

    public function test_endpoint_responde_403_aunque_el_menu_lo_oculte(): void
    {
        $laura = $this->usuarioDe('Ingeniería de proyectos');

        $this->actingAs($laura)->getJson('/api/me')->assertJsonPath('data.permisos.usuarios_permisos.nivel', 'sin_acceso');
        $this->actingAs($laura)->getJson('/api/usuarios')->assertForbidden()->assertJsonStructure(['data', 'message', 'errors']);
        $this->actingAs($laura)->getJson('/api/plazos-prioridad')->assertForbidden();
    }

    public function test_usuarios_ver_no_cambia_la_tabla(): void
    {
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');
        $celda = ['sector_id' => $compras, 'funcion' => 'proveedores', 'nivel' => 'ver', 'alcance' => 'todas'];

        $this->actingAs($this->usuarioDe('Dirección'))->putJson('/api/permisos', ['celdas' => [$celda]])->assertOk();
        $this->actingAs($this->usuarioDe('Jefatura de Proyectos'))->putJson('/api/permisos', ['celdas' => [$celda]])->assertForbidden();
    }

    public function test_no_acepta_editar_en_funcion_que_no_lo_admite(): void
    {
        // Hoy las 12 funciones admiten editar (§6.1); la regla queda probada con una que no lo admita.
        Funcion::query()->where('clave', 'monto_obra')->update(['admite_editar' => false]);
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');

        $this->actingAs($this->administrador())->putJson('/api/permisos', ['celdas' => [
            ['sector_id' => $compras, 'funcion' => 'monto_obra', 'nivel' => 'editar', 'alcance' => 'todas'],
        ]])->assertUnprocessable()->assertJsonPath('errors', ['celdas.0.nivel' => [CambiarCeldasAction::NO_ADMITE_EDITAR]]);
    }

    public function test_no_acepta_propias_en_funcion_sin_alcance(): void
    {
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');

        $this->actingAs($this->administrador())->putJson('/api/permisos', ['celdas' => [
            ['sector_id' => $compras, 'funcion' => 'proveedores', 'nivel' => 'ver', 'alcance' => 'propias'],
        ]])->assertUnprocessable()->assertJsonPath('errors', ['celdas.0.alcance' => [CambiarCeldasAction::NO_ADMITE_ALCANCE]]);
    }

    public function test_no_acepta_funcion_inexistente_ni_nivel_invalido(): void
    {
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');

        $this->actingAs($this->administrador())->putJson('/api/permisos', ['celdas' => [
            ['sector_id' => $compras, 'funcion' => 'tango', 'nivel' => 'todo', 'alcance' => 'todas'],
        ]])->assertUnprocessable()->assertJsonValidationErrors(['celdas.0.funcion', 'celdas.0.nivel']);
    }

    public function test_cambio_de_celda_queda_en_actividades(): void
    {
        $ingenieria = Sector::query()->where('nombre', 'Ingeniería de proyectos')->valueOrFail('id');

        $this->actingAs($this->administrador())->putJson('/api/permisos', ['celdas' => [
            ['sector_id' => $ingenieria, 'funcion' => 'rendicion', 'nivel' => 'ver', 'alcance' => 'todas'],
            ['sector_id' => $ingenieria, 'funcion' => 'obras', 'nivel' => 'editar', 'alcance' => 'todas'],
        ]])->assertOk()->assertJsonPath('message', 'Guardamos la tabla de permisos.');

        $registros = $this->actividad->de(Permiso::class, 'editado');
        $this->assertCount(1, $registros, 'Solo la celda que cambió');
        $this->assertSame([
            'nivel' => ['antes' => 'ver', 'despues' => 'ver'],
            'alcance' => ['antes' => 'propias', 'despues' => 'todas'],
        ], $registros[0]['cambios']);
    }

    public function test_cambio_de_matriz_es_todo_o_nada(): void
    {
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');
        $archivado = Sector::factory()->archivado()->create();

        $this->actingAs($this->administrador())->putJson('/api/permisos', ['celdas' => [
            ['sector_id' => $compras, 'funcion' => 'obras', 'nivel' => 'editar', 'alcance' => 'todas'],
            ['sector_id' => $archivado->id, 'funcion' => 'obras', 'nivel' => 'ver', 'alcance' => 'todas'],
        ]])->assertUnprocessable()->assertJsonValidationErrors(['celdas.1.sector_id']);

        $this->assertSame('ver', $this->nivel('Compras', 'obras'));
        $this->assertSame([], $this->actividad->registros);
    }

    public function test_no_se_puede_dejar_sin_administrador_desde_la_tabla(): void
    {
        $admin = $this->administrador();
        $gerencia = Sector::query()->where('nombre', 'Gerencia de Administración')->valueOrFail('id');

        $this->actingAs($admin)->putJson('/api/permisos', ['celdas' => [
            ['sector_id' => $gerencia, 'funcion' => 'obras', 'nivel' => 'ver', 'alcance' => 'todas'],
            ['sector_id' => $gerencia, 'funcion' => 'usuarios_permisos', 'nivel' => 'ver', 'alcance' => 'todas'],
        ]])->assertUnprocessable()->assertJsonPath('errors.celdas.0', GuardaAdministradores::SIN_ADMINISTRADOR);

        $this->assertSame('editar', $this->nivel('Gerencia de Administración', 'usuarios_permisos'));
        $this->assertSame('editar', $this->nivel('Gerencia de Administración', 'obras'));
    }

    private function nivel(string $sector, string $funcion): string
    {
        return Permiso::query()
            ->whereHas('sector', fn ($query) => $query->where('nombre', $sector))
            ->whereHas('funcion', fn ($query) => $query->where('clave', $funcion))
            ->firstOrFail()->nivel->value;
    }
}
