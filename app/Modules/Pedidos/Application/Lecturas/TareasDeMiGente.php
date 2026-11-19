<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Tareas de mi gente (W-26, RN-PED-41): un grupo por persona, incluida yo, con sus pedidos por entrega
 * (vencidos arriba). Grupos por vencidas y después abiertas, de mayor a menor; también las personas sin
 * tareas abiertas (resuelta DU-P14) y las inactivas que todavía tienen abiertas (resuelta DU-P09). Los
 * pedidos de toda la gente salen en una sola consulta (más las de sus relaciones).
 */
final readonly class TareasDeMiGente
{
    public function __construct(private GenteConCarga $gente) {}

    /**
     * @return list<array{carga: CargaDePersona, pedidos: list<Pedido>}>
     *
     * @throws HttpResponseException 403 sin gente a cargo
     */
    public function grupos(User $user, string $estado): array
    {
        $cargas = $this->gente->de($user);
        usort($cargas, static fn (CargaDePersona $a, CargaDePersona $b): int => [$b->vencidas, $b->abiertas] <=> [$a->vencidas, $a->abiertas]);
        $ids = array_map(static fn (CargaDePersona $c): int => $c->persona->id, $cargas);
        $query = PedidosQuery::porEstado(PedidosQuery::nueva(ListasDeInicio::CON), $estado)->whereIn('pedidos.propietario_id', $ids);
        $porPersona = PedidosQuery::porEntrega($query)->get()->groupBy('propietario_id');

        return array_map(static fn (CargaDePersona $c): array => [
            'carga' => $c,
            'pedidos' => array_values($porPersona->get($c->persona->id)?->all() ?? []),
        ], $cargas);
    }
}
