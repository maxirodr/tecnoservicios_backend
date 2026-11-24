<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

/**
 * Lo que se carga o corrige de un material de la lista (W-48, rendicion.md §4.2), ya validado en el borde.
 * `presentes` dice qué campos vinieron (en la corrección todos son opcionales).
 */
final readonly class DatosMaterial
{
    public const CAMPOS = ['nombre', 'unidad', 'cantidad_presupuestada', 'precio_presupuestado', 'presupuesto_id'];

    /**
     * @param  numeric-string|null  $cantidadPresupuestada
     * @param  numeric-string|null  $precioPresupuestado
     * @param  list<string>  $presentes
     */
    public function __construct(
        public ?string $nombre,
        public ?string $unidad,
        public ?string $cantidadPresupuestada,
        public ?string $precioPresupuestado,
        public ?int $presupuestoId,
        public array $presentes = self::CAMPOS,
    ) {}

    public function trae(string $campo): bool
    {
        return in_array($campo, $this->presentes, true);
    }
}
