<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Resources;

use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Models\PedidoComentario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Comentario` (§11.1): autor, texto y fecha y hora ("Carla · 14-oct 9:12"). Cargar `autor`.
 *
 * @property PedidoComentario $resource
 */
final class ComentarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::comentario($this->resource);
    }

    /**
     * @return array<string, mixed>
     */
    public static function comentario(PedidoComentario $comentario): array
    {
        return [
            'id' => $comentario->id,
            'autor' => PedidoFilaResource::persona($comentario->autor),
            'texto' => $comentario->texto,
            'created_at' => FechaEmpresa::instante($comentario->created_at),
        ];
    }
}
