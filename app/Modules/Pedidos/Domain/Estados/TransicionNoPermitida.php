<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Estados;

use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use DomainException;

/** Acción que no corresponde al estado actual (RN-PED-15): 409 con `data.estado` y sin repetir avisos. */
final class TransicionNoPermitida extends DomainException
{
    public const COMPLETADO = 'Está completado: si falta algo, reabrilo.';

    public const BLOQUEADO = 'Primero retomalo: está bloqueado.';

    private function __construct(string $mensaje, public readonly PedidoEstado $actual)
    {
        parent::__construct($mensaje);
    }

    public static function para(AccionPedido $accion, PedidoEstado $actual, ?string $propietario = null): self
    {
        $mensaje = match (true) {
            $accion === AccionPedido::Completar && $actual === PedidoEstado::Completada => 'Ya está completado.',
            $accion === AccionPedido::Completar && $actual === PedidoEstado::Bloqueada => self::BLOQUEADO,
            $accion === AccionPedido::Reabrir => 'No está completado: no hace falta reabrirlo.',
            $actual === PedidoEstado::Completada => self::COMPLETADO,
            $accion === AccionPedido::Tomar => $propietario === null ? 'Ya lo tomaron.' : "Ya lo tomó {$propietario}.",
            $accion === AccionPedido::Bloquear => 'Ya está bloqueado.',
            default => 'No está bloqueado.',
        };

        return new self($mensaje, $actual);
    }
}
