<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Equipos;

/**
 * Lo que vino en el alta o la edición de un equipo (W-49): solo los campos presentes. `inventario` y
 * `presupuesto` = id del adjunto temporal; en la edición, `null` lo quita (§11.2).
 */
final readonly class DatosEquipo
{
    public const INVENTARIO = 'inventario';

    public const PRESUPUESTO = 'presupuesto';

    /**
     * @param  array<string, string|null>  $campos  equipo, codigo, trabajo, taller, estado, aprobacion, fecha (AAAA-MM-DD), notas
     * @param  array<string, int|null>  $adjuntos  `inventario` y/o `presupuesto`, solo los que vinieron
     */
    public function __construct(
        public array $campos,
        public array $adjuntos,
    ) {}
}
