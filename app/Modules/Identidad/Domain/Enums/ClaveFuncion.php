<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Enums;

/**
 * Claves del catálogo de funciones (filas de la tabla de permisos, identidad.md §6.1).
 * El catálogo vive en la tabla `funciones` (cargada por migración); esto evita strings sueltos.
 */
enum ClaveFuncion: string
{
    case Pedidos = 'pedidos';
    case EquiposReparacion = 'equipos_reparacion';
    case Obras = 'obras';
    case EstadoObra = 'estado_obra';
    case PresupuestoRubro = 'presupuesto_rubro';
    case MontoObra = 'monto_obra';
    case Gastos = 'gastos';
    case Rendicion = 'rendicion';
    case ControlCantidad = 'control_cantidad';
    case Proveedores = 'proveedores';
    case UsuariosPermisos = 'usuarios_permisos';
    case PlazosPrioridad = 'plazos_prioridad';
}
