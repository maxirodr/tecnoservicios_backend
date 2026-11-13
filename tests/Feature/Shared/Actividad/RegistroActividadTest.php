<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Actividad;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\Actividad;
use App\Shared\Actividad\HistorialActividad;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\TestCase;

/**
 * ADR-010 · F-14: quién hizo qué, desde el caso de uso, en su transacción. Solo inserción.
 */
final class RegistroActividadTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_sujeto_usuario_autenticado_accion_y_cambios(): void
    {
        $autor = User::factory()->create();
        $sujeto = User::factory()->create();
        $this->actingAs($autor);

        $this->registro()->registrar($sujeto, 'editado', ['email' => ['antes' => 'viejo@example.com', 'despues' => 'nuevo@example.com']]);

        $actividad = Actividad::query()->sole();
        $this->assertSame($sujeto->getMorphClass(), $actividad->sujeto_type);
        $this->assertSame($sujeto->id, $actividad->sujeto_id);
        $this->assertSame($autor->id, $actividad->user_id);
        $this->assertSame('editado', $actividad->accion);
        $this->assertSame(['email' => ['antes' => 'viejo@example.com', 'despues' => 'nuevo@example.com']], $actividad->cambios);
    }

    public function test_accion_sin_cambios_guarda_cambios_vacios(): void
    {
        $this->actingAs(User::factory()->create());

        $this->registro()->registrar(User::factory()->create(), 'creado');

        $this->assertNull(Actividad::query()->sole()->cambios);
    }

    public function test_accion_del_sistema_queda_sin_usuario(): void
    {
        $this->registro()->registrar(User::factory()->create(), 'recordatorio_enviado');

        $this->assertNull(Actividad::query()->sole()->user_id);
    }

    public function test_actividad_queda_dentro_de_la_transaccion_del_caso_de_uso(): void
    {
        $sujeto = User::factory()->create();

        try {
            DB::transaction(function () use ($sujeto): void {
                $this->registro()->registrar($sujeto, 'editado');

                throw new RuntimeException('el caso de uso falla');
            });
        } catch (RuntimeException) {
            // la transacción del caso de uso se deshace
        }

        $this->assertSame(0, Actividad::query()->count());
    }

    public function test_actividad_es_solo_insercion(): void
    {
        $this->registro()->registrar(User::factory()->create(), 'creado');
        $actividad = Actividad::query()->sole();

        try {
            $actividad->forceFill(['accion' => 'otra'])->save();
            $this->fail('Una actividad no se puede editar.');
        } catch (LogicException) {
            $this->assertSame('creado', Actividad::query()->sole()->accion);
        }

        $this->expectException(LogicException::class);
        $actividad->delete();
    }

    public function test_historial_trae_solo_el_sujeto_lo_mas_nuevo_primero_y_quien(): void
    {
        $autor = User::factory()->create();
        $sujeto = User::factory()->create();
        $otro = User::factory()->create();
        $this->actingAs($autor);

        $this->travelTo(now()->subHour());
        $this->registro()->registrar($sujeto, 'creado');
        $this->travelBack();
        $this->registro()->registrar($sujeto, 'editado');
        $this->registro()->registrar($otro, 'editado');

        $historial = $this->app->make(HistorialActividad::class)->delSujeto($sujeto);

        $this->assertSame(2, $historial->total());
        $this->assertSame(['editado', 'creado'], collect($historial->items())->pluck('accion')->all());
        $primera = $historial->items()[0];
        $this->assertTrue($primera->relationLoaded('user'));
        $this->assertSame($autor->id, $primera->user?->id);
    }

    private function registro(): RegistroActividad
    {
        return $this->app->make(RegistroActividad::class);
    }
}
