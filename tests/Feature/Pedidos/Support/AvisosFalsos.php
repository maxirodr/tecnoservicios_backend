<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos\Support;

use App\Modules\Avisos\Contracts\AvisoEnviado;
use App\Modules\Avisos\Contracts\AvisosDelPedido;
use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\Participantes;
use Illuminate\Contracts\Container\Container;

/**
 * Lo que implementa Avisos, en memoria: la asignación le llega al propietario o a las personas activas
 * del sector, nunca a quien hace la acción (avisos.md RN-AVI-08/09), y los avisos enviados se cargan a mano.
 */
final class AvisosFalsos implements AvisosDelPedido, DestinatariosDeAviso
{
    /** @var array<int, list<AvisoEnviado>> */
    public array $enviados = [];

    public static function registrar(Container $app): self
    {
        $falsos = new self;
        $app->instance(AvisosDelPedido::class, $falsos);
        $app->instance(DestinatariosDeAviso::class, $falsos);

        return $falsos;
    }

    public function enviadosDe(int $pedidoId): array
    {
        return $this->enviados[$pedidoId] ?? [];
    }

    public function deAsignacion(Participantes $participantes, int $actorId): array
    {
        $query = User::query()->where('activo', true)->whereKeyNot($actorId)->orderBy('id');

        $query = $participantes->propietarioId !== null
            ? $query->whereKey($participantes->propietarioId)
            : $query->where('sector_id', $participantes->sectorId);

        return array_values($query->get()->all());
    }
}
