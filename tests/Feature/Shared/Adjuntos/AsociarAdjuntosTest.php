<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;

/**
 * HU-025 criterio 5 · RN-IDE-46: solo se asocian temporales propios y del contexto del campo.
 */
final class AsociarAdjuntosTest extends AdjuntosTestCase
{
    public function test_asociar_deja_el_adjunto_en_el_recurso_y_registra_actividad(): void
    {
        $autora = $this->usuario();
        $adjunto = $this->adjuntoSubido($autora);
        $documento = $this->documento();

        $this->actingAs($autora);
        $this->service()->asociar([$adjunto->id], $documento, self::CONTEXTO);

        $adjunto->refresh();
        $this->assertFalse($adjunto->esTemporal());
        $this->assertTrue($adjunto->perteneceA($documento));

        $actividad = Actividad::query()->sole();
        $this->assertSame(AdjuntosService::ACCION_AGREGADO, $actividad->accion);
        $this->assertSame($autora->id, $actividad->user_id);
        $this->assertSame([self::CONTEXTO => ['antes' => null, 'despues' => 'oferta-ejemplo.pdf']], $actividad->cambios);
    }

    public function test_no_se_asocia_adjunto_subido_por_otro(): void
    {
        $ajeno = $this->adjuntoSubido($this->usuario());

        $this->actingAs($this->usuario());
        $this->assertNoUtilizable(fn () => $this->service()->asociar([$ajeno->id], $this->documento(), self::CONTEXTO));

        $this->assertTrue($ajeno->refresh()->esTemporal());
    }

    public function test_no_se_asocia_adjunto_de_otro_contexto(): void
    {
        $autora = $this->usuario();
        $deOtroContexto = $this->adjuntoSubido($autora, self::OTRO_CONTEXTO);

        $this->actingAs($autora);
        $this->assertNoUtilizable(fn () => $this->service()->asociar([$deOtroContexto->id], $this->documento(), self::CONTEXTO));
    }

    public function test_no_se_asocia_adjunto_ya_usado(): void
    {
        $autora = $this->usuario();
        $adjunto = $this->adjuntoDe($this->documento(), $autora);

        $this->actingAs($autora);
        $this->assertNoUtilizable(fn () => $this->service()->asociar([$adjunto->id], $this->documento(), self::CONTEXTO));
    }

    public function test_si_un_adjunto_no_sirve_no_se_asocia_ninguno(): void
    {
        $autora = $this->usuario();
        $propio = $this->adjuntoSubido($autora);
        $ajeno = $this->adjuntoSubido($this->usuario());

        $this->actingAs($autora);
        $this->assertNoUtilizable(fn () => $this->service()->asociar([$propio->id, $ajeno->id, 999999], $this->documento(), self::CONTEXTO));

        $this->assertTrue($propio->refresh()->esTemporal());
        $this->assertSame(0, Actividad::query()->count());
    }

    public function test_alta_fallida_deja_los_adjuntos_temporales(): void
    {
        $autora = $this->usuario();
        $adjunto = $this->adjuntoSubido($autora);
        $this->actingAs($autora);

        try {
            DB::transaction(function () use ($adjunto): void {
                $this->service()->asociar([$adjunto->id], $this->documento(), self::CONTEXTO);

                throw new RuntimeException('falla el caso de uso después de asociar');
            });
        } catch (RuntimeException) {
            // el caso de uso falló: su transacción se deshace entera
        }

        $this->assertTrue(Adjunto::query()->findOrFail($adjunto->id)->esTemporal());
        $this->assertSame(0, Actividad::query()->count());
    }

    public function test_lista_vacia_no_hace_nada(): void
    {
        $this->actingAs($this->usuario());
        $this->service()->asociar([], $this->documento(), self::CONTEXTO);

        $this->assertSame(0, Actividad::query()->count());
    }

    private function service(): AdjuntosService
    {
        return $this->app->make(AdjuntosService::class);
    }

    private function assertNoUtilizable(callable $accion): void
    {
        try {
            $accion();
            $this->fail('Se esperaba 422 "Ese adjunto no se puede usar acá."');
        } catch (ValidationException $e) {
            $this->assertSame([AdjuntosService::CAMPO_ERROR => [AdjuntosService::MENSAJE_NO_UTILIZABLE]], $e->errors());
        }
    }
}
