<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Resources;

use App\Modules\Pedidos\Application\Lecturas\CargaDePersona;
use App\Modules\Pedidos\Domain\Models\Pedido;

/** Formas JSON de Tareas de mi gente y Carga por persona (pedidos.md §11.1). */
final class MiGenteResource
{
    /**
     * @param  array{carga: CargaDePersona, pedidos: list<Pedido>}  $grupo
     * @return array<string, mixed>
     */
    public static function grupo(array $grupo): array
    {
        $carga = $grupo['carga'];

        return [
            'persona' => [...self::persona($carga), 'sector' => self::sector($carga)],
            'abiertas' => $carga->abiertas,
            'vencidas' => $carga->vencidas,
            'pedidos' => array_map(PedidoFilaResource::fila(...), $grupo['pedidos']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fila(CargaDePersona $carga): array
    {
        return [
            'persona' => self::persona($carga),
            'sector' => self::sector($carga),
            'abiertas' => $carga->abiertas,
            'vencidas' => $carga->vencidas,
            'vencen_esta_semana' => $carga->vencenEstaSemana,
        ];
    }

    /**
     * @return array{id: int, nombre: string, activo: bool, soy_yo: bool}
     */
    private static function persona(CargaDePersona $carga): array
    {
        return ['id' => $carga->persona->id, 'nombre' => $carga->persona->nombre, 'activo' => $carga->persona->activo, 'soy_yo' => $carga->soyYo];
    }

    /**
     * @return array{id: int, nombre: string}
     */
    private static function sector(CargaDePersona $carga): array
    {
        return ['id' => $carga->persona->sector->id, 'nombre' => $carga->persona->sector->nombre];
    }
}
