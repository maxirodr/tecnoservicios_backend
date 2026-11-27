<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Contracts;

use App\Shared\CargaInicial\CargaRechazada;

/**
 * Las tareas abiertas de la minuta (`importacion.md` §11.4, RN-IMP-32…37): Pedidos las crea con las validaciones del
 * alta (obra, propietario activo de un sector con Tareas ≥ Ver o el sector, fechas, links), salvo solicitante y fecha
 * de pedido, que pueden faltar (DU-20). Quedan con `carga_inicial = true`, el estado de la minuta y **sin avisos**:
 * no se dispara `PedidoAsignado` (RN-IMP-37, resuelta DU-I05).
 */
interface CargaInicialDeTareas
{
    /**
     * @throws CargaRechazada con los textos de la pantalla o de `importacion.md` §8
     */
    public function crear(int $actorId, TareaInicial $tarea): int;

    /**
     * La prioridad que calcula el sistema con los plazos vigentes (W-50), o null si falta una de las dos fechas.
     *
     * @return 'urgente'|'prioritario'|'normal'|null
     */
    public function prioridadCalculada(?string $fechaPedido, string $fechaEntrega): ?string;
}
