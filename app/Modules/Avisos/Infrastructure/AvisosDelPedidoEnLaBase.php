<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Infrastructure;

use App\Modules\Avisos\Contracts\AvisoEnviado;
use App\Modules\Avisos\Contracts\AvisosDelPedido;
use App\Modules\Avisos\Domain\Models\Aviso;
use DateTimeImmutable;

/**
 * Avisos de un pedido agrupados por evento, para "sistema avisó a Carla (asignado)" del recorrido
 * (RN-AVI-28). Un evento = el `dedupe_key` sin la persona del final; el aviso al superior queda en su propio
 * grupo porque tiene otro tipo.
 */
final class AvisosDelPedidoEnLaBase implements AvisosDelPedido
{
    public function enviadosDe(int $pedidoId): array
    {
        $avisos = Aviso::query()->with('user:id,nombre')->where('pedido_id', $pedidoId)->orderBy('id')->get();

        /** @var array<string, array{tipo: string, destinatarios: list<array{id: int, nombre: string}>, created_at: DateTimeImmutable}> $grupos */
        $grupos = [];
        foreach ($avisos as $aviso) {
            $evento = substr($aviso->dedupe_key, 0, (int) strrpos($aviso->dedupe_key, ':'));
            $grupos[$evento] ??= [
                'tipo' => $aviso->tipo->value,
                'destinatarios' => [],
                'created_at' => $aviso->created_at->toDateTimeImmutable(),
            ];
            $grupos[$evento]['destinatarios'][] = ['id' => $aviso->user_id, 'nombre' => $aviso->user->nombre];
        }

        return array_values(array_map(
            static fn (array $grupo): AvisoEnviado => new AvisoEnviado($grupo['tipo'], $grupo['destinatarios'], $grupo['created_at']),
            $grupos,
        ));
    }
}
