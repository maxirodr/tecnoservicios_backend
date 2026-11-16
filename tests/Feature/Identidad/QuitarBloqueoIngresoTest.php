<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Usuarios\QuitarBloqueoIngresoAction;
use App\Modules\Identidad\Domain\Models\BloqueoIngreso;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\Actividad;

/** PU-045 · DU-Q06: gerencia ve y destraba el bloqueo del ingreso (SEG-01) desde Usuarios. */
final class QuitarBloqueoIngresoTest extends IdentidadTestCase
{
    public function test_el_usuario_muestra_hasta_cuando_esta_bloqueado(): void
    {
        $admin = $this->administrador();
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla']);
        $hasta = $this->bloquear($carla);

        $this->actingAs($admin)->getJson("/api/usuarios/{$carla->id}")
            ->assertOk()
            ->assertJsonPath('data.bloqueo_ingreso', ['hasta' => $hasta]);
        $listado = (array) $this->actingAs($admin)->getJson('/api/usuarios?buscar=Carla')->assertOk()->json('data');
        $this->assertSame(['hasta' => $hasta], $listado[0]['bloqueo_ingreso']);

        $this->actingAs($admin)->getJson("/api/usuarios/{$admin->id}")->assertJsonPath('data.bloqueo_ingreso', null);
    }

    public function test_quien_edita_usuarios_destraba_el_ingreso_y_queda_registrado(): void
    {
        $admin = $this->administrador();
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla']);
        $hasta = $this->bloquear($carla);

        $this->actingAs($admin)->deleteJson("/api/usuarios/{$carla->id}/bloqueo-ingreso")
            ->assertOk()
            ->assertJsonPath('message', 'Destrabamos el ingreso de Carla.')
            ->assertJsonPath('data.id', $carla->id)
            ->assertJsonPath('data.bloqueo_ingreso', null);

        $this->assertSame(0, BloqueoIngreso::query()->where('user_id', $carla->id)->count());
        $actividad = Actividad::query()->where('accion', QuitarBloqueoIngresoAction::ACCION)->sole();
        $this->assertSame($carla->id, (int) $actividad->sujeto_id);
        $this->assertSame($admin->id, (int) $actividad->user_id);
        $this->assertSame(['bloqueo_ingreso' => ['antes' => $hasta, 'despues' => null]], $actividad->cambios);
    }

    public function test_sin_bloqueo_activo_responde_igual_y_no_registra_nada(): void
    {
        $admin = $this->administrador();
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla S.R.L.']);
        BloqueoIngreso::query()->create(['user_id' => $carla->id, 'codigos_quemados' => 2]);

        $this->actingAs($admin)->deleteJson("/api/usuarios/{$carla->id}/bloqueo-ingreso")
            ->assertOk()
            ->assertJsonPath('message', 'Destrabamos el ingreso de Carla S.R.L.');
        $this->actingAs($admin)->deleteJson("/api/usuarios/{$carla->id}/bloqueo-ingreso")->assertOk();

        $this->assertSame(0, Actividad::query()->where('accion', QuitarBloqueoIngresoAction::ACCION)->count());
        $this->assertSame(2, BloqueoIngreso::query()->where('user_id', $carla->id)->value('codigos_quemados'));
    }

    public function test_sin_permiso_de_editar_usuarios_da_403_y_no_destraba(): void
    {
        $carla = $this->usuarioDe('Compras');
        $this->bloquear($carla);

        $this->actingAs($this->usuarioDe('Compras'))->deleteJson("/api/usuarios/{$carla->id}/bloqueo-ingreso")
            ->assertForbidden()
            ->assertJsonPath('data', null);

        $this->assertSame(1, BloqueoIngreso::query()->where('user_id', $carla->id)->count());
    }

    public function test_usuario_inexistente_da_404(): void
    {
        $this->actingAs($this->administrador())->deleteJson('/api/usuarios/999999/bloqueo-ingreso')
            ->assertNotFound()
            ->assertJsonPath('data', null);
    }

    private function bloquear(User $user): string
    {
        $bloqueo = BloqueoIngreso::query()->create([
            'user_id' => $user->id,
            'bloqueos_cumplidos' => 1,
            'bloqueado_hasta' => now()->addMinutes(40)->startOfSecond(),
        ]);

        return (string) $bloqueo->bloqueado_hasta?->toIso8601String();
    }
}
