<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Acceso;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Database\Connection;

/** Si una persona sigue un pedido (F-07). Usa la relación si ya está cargada; si no, una consulta. */
final readonly class Seguimiento
{
    public const TABLA = 'pedido_seguidores';

    public function __construct(private Connection $db) {}

    public function sigue(User $user, Pedido $pedido): bool
    {
        if ($pedido->relationLoaded('seguidores')) {
            return $pedido->seguidores->contains('id', $user->id);
        }

        return $this->db->table(self::TABLA)->where('pedido_id', $pedido->id)->where('user_id', $user->id)->exists();
    }

    /**
     * @return list<int>
     */
    public function seguidorIds(int $pedidoId): array
    {
        return array_values($this->db->table(self::TABLA)->where('pedido_id', $pedidoId)->orderBy('id')->pluck('user_id')
            ->map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)->all());
    }
}
