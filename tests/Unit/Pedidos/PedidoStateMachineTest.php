<?php

declare(strict_types=1);

namespace Tests\Unit\Pedidos;

use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Estados\AccionPedido;
use App\Modules\Pedidos\Domain\Estados\PedidoStateMachine;
use App\Modules\Pedidos\Domain\Estados\TransicionNoPermitida;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** pedidos.md §6.1: una aserción por fila de la tabla, y lo que no está en la tabla es 409. */
final class PedidoStateMachineTest extends TestCase
{
    /**
     * @return array<string, array{0: AccionPedido, 1: PedidoEstado, 2: PedidoEstado}>
     */
    public static function permitidas(): array
    {
        return [
            'tomar desde no iniciada' => [AccionPedido::Tomar, PedidoEstado::NoIniciada, PedidoEstado::EnCurso],
            'bloquear desde no iniciada (DU-P03)' => [AccionPedido::Bloquear, PedidoEstado::NoIniciada, PedidoEstado::Bloqueada],
            'bloquear desde en curso' => [AccionPedido::Bloquear, PedidoEstado::EnCurso, PedidoEstado::Bloqueada],
            'retomar desde bloqueada' => [AccionPedido::Retomar, PedidoEstado::Bloqueada, PedidoEstado::EnCurso],
            'completar desde no iniciada (DU-15)' => [AccionPedido::Completar, PedidoEstado::NoIniciada, PedidoEstado::Completada],
            'completar desde en curso' => [AccionPedido::Completar, PedidoEstado::EnCurso, PedidoEstado::Completada],
            'reabrir desde completada' => [AccionPedido::Reabrir, PedidoEstado::Completada, PedidoEstado::EnCurso],
            'reasignar no iniciada' => [AccionPedido::Reasignar, PedidoEstado::NoIniciada, PedidoEstado::NoIniciada],
            'reasignar en curso' => [AccionPedido::Reasignar, PedidoEstado::EnCurso, PedidoEstado::EnCurso],
            'reasignar bloqueada' => [AccionPedido::Reasignar, PedidoEstado::Bloqueada, PedidoEstado::Bloqueada],
        ];
    }

    #[DataProvider('permitidas')]
    public function test_cada_fila_de_la_tabla_lleva_a_su_estado(AccionPedido $accion, PedidoEstado $desde, PedidoEstado $hasta): void
    {
        $this->assertSame($hasta, (new PedidoStateMachine)->destino($accion, $desde));
    }

    public function test_lo_que_no_esta_en_la_tabla_no_se_permite(): void
    {
        $maquina = new PedidoStateMachine;
        $permitidas = array_map(static fn (array $f): string => $f[0]->value.'|'.$f[1]->value, self::permitidas());

        foreach (AccionPedido::cases() as $accion) {
            foreach (PedidoEstado::cases() as $estado) {
                $this->assertSame(in_array($accion->value.'|'.$estado->value, $permitidas, true), $maquina->permite($accion, $estado));
            }
        }
    }

    public function test_completar_desde_bloqueada_pide_retomarlo(): void
    {
        $this->expectExceptionObject(TransicionNoPermitida::para(AccionPedido::Completar, PedidoEstado::Bloqueada));
        $this->expectExceptionMessage('Primero retomalo: está bloqueado.');

        (new PedidoStateMachine)->destino(AccionPedido::Completar, PedidoEstado::Bloqueada);
    }

    public function test_tomar_uno_tomado_dice_quien_lo_tomo(): void
    {
        try {
            (new PedidoStateMachine)->destino(AccionPedido::Tomar, PedidoEstado::EnCurso, 'Carla');
            $this->fail('Se esperaba TransicionNoPermitida');
        } catch (TransicionNoPermitida $e) {
            $this->assertSame('Ya lo tomó Carla.', $e->getMessage());
            $this->assertSame(PedidoEstado::EnCurso, $e->actual);
        }
    }
}
