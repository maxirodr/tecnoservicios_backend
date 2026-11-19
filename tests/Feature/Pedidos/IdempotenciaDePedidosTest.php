<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** RN-PED-59 · ADR-011: cada POST de Pedidos acepta `Idempotency-Key`; el mismo envío no repite la acción. */
final class IdempotenciaDePedidosTest extends PedidosTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function rutas(): array
    {
        return [
            'tomar' => ['tomar', 'propietario', []],
            'bloquear' => ['bloquear', 'propietario', ['motivo' => 'Esperando presupuesto']],
            'completar' => ['completar', 'propietario', ['comentario' => 'Listo.']],
            'reasignar' => ['reasignar', 'propietario', ['propietario_id' => 'diego']],
            'comentarios' => ['comentarios', 'solicitante', ['texto' => '30 bolsas.']],
            'seguir' => ['seguir', 'tercero', []],
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    #[DataProvider('rutas')]
    public function test_cada_post_de_pedidos_acepta_idempotency_key(string $ruta, string $quien, array $datos): void
    {
        $actores = ['solicitante' => $this->laura(), 'propietario' => $this->carla(), 'tercero' => $this->sofia()];
        $diego = $this->diego();
        $pedido = $this->crear($actores['solicitante'], $actores['propietario']);
        $datos = array_map(static fn (mixed $v): mixed => $v === 'diego' ? $diego->id : $v, $datos);
        $actividades = Actividad::query()->count();

        $this->enviarDosVeces($actores[$quien], "/api/pedidos/{$pedido->id}/{$ruta}", $datos);

        $this->assertLessThanOrEqual($actividades + 1, Actividad::query()->count());
    }

    public function test_retomar_y_reabrir_aceptan_idempotency_key(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'x'])->assertOk();

        $this->enviarDosVeces($carla, "/api/pedidos/{$pedido->id}/retomar", []);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $this->enviarDosVeces($laura, "/api/pedidos/{$pedido->id}/reabrir", ['comentario' => 'Falta algo.']);

        $this->assertSame(1, Actividad::query()->where('accion', 'reabierto')->count());
        $this->assertSame(1, Actividad::query()->where('accion', 'retomado')->count());
    }

    public function test_alta_con_la_misma_clave_y_otro_contenido_es_rechazada(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $clave = (string) Str::uuid();

        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($carla), ['Idempotency-Key' => $clave])->assertCreated();
        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($carla, null, ['tarea' => 'Otra']), ['Idempotency-Key' => $clave])->assertUnprocessable();
        $this->assertSame(1, Pedido::query()->count());
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function enviarDosVeces(User $user, string $url, array $datos): void
    {
        $clave = (string) Str::uuid();
        $primera = $this->actingAs($user)->postJson($url, $datos, ['Idempotency-Key' => $clave]);
        $this->assertTrue($primera->isSuccessful(), (string) $primera->getContent());

        $this->actingAs($user)->postJson($url, $datos, ['Idempotency-Key' => $clave])
            ->assertStatus($primera->getStatusCode())
            ->assertHeader('Idempotent-Replayed');
    }
}
