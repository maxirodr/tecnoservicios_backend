<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure;

use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Consultas\PorTexto;
use App\Modules\Pedidos\Infrastructure\Consultas\VisiblesPara;
use App\Shared\Busqueda\FuenteBusqueda;
use App\Shared\Busqueda\ResultadoBusqueda;
use Illuminate\Database\Eloquent\Builder;

/**
 * Grupo "Pedidos" del buscador general (F-03, HU-057, RN-PED-51): por texto en la tarea, con quién y
 * aclaraciones, en cualquier estado y de obras terminadas también. Abiertos primero (por entrega), después
 * completados (el más reciente primero). Con el alcance de quien busca; sin Pedidos ≥ Ver no viaja.
 */
final readonly class PedidosEnBuscador implements FuenteBusqueda
{
    public const TIPO = 'pedidos';

    public function __construct(private AccesoPedido $acceso) {}

    public function puedeBuscar(User $user): bool
    {
        return $this->acceso->puedeNivel($user, Nivel::Ver);
    }

    public function buscar(string $texto, User $user, int $limite): ResultadoBusqueda
    {
        $completada = PedidoEstado::Completada->value;
        $cantidad = $this->encontrados($texto, $user)->count();
        $primeros = $this->encontrados($texto, $user)
            ->with(['obra', 'propietario', 'sector'])
            ->orderByRaw('pedidos.estado = ?', [$completada])
            ->orderByRaw('CASE WHEN pedidos.estado = ? THEN pedidos.completado_at END DESC', [$completada])
            ->orderByRaw('pedidos.fecha_entrega IS NULL')
            ->orderBy('pedidos.fecha_entrega')
            ->orderByDesc('pedidos.id')
            ->limit($limite)
            ->get();

        return new ResultadoBusqueda($cantidad, array_values($primeros->map(static fn (Pedido $p): array => [
            'id' => $p->id,
            'tarea' => $p->tarea,
            'obra' => $p->obra === null ? null : ['id' => $p->obra->id, 'nombre_trabajo' => $p->obra->nombre_trabajo],
            'propietario' => $p->propietario === null ? null : ['id' => $p->propietario->id, 'nombre' => $p->propietario->nombre],
            'sector' => ['id' => $p->sector->id, 'nombre' => $p->sector->nombre],
            'estado' => $p->estado->value,
        ])->all()));
    }

    /**
     * @return Builder<Pedido>
     */
    private function encontrados(string $texto, User $user): Builder
    {
        $query = VisiblesPara::aplicar(Pedido::query(), $this->acceso->visibilidad($user));
        (new PorTexto($texto))->aplicar($query);

        return $query;
    }
}
