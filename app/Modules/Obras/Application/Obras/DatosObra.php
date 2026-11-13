<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Obras\Application\Presupuestos\DatosPresupuesto;

/**
 * Alta de obra (ObraAlta, obras.md §10.1), validada y normalizada en el borde.
 *
 * @phpstan-type Contacto array{nombre: string, email: string, telefono: string}
 */
final readonly class DatosObra
{
    /**
     * @param  list<DatosPresupuesto>  $adicionales
     * @param  array{solicitante: Contacto, comprador: Contacto}  $contactos
     */
    public function __construct(
        public string $nombreTrabajo,
        public string $cliente,
        public ?string $planta,
        public int $responsableId,
        public ?string $fechaInicioPrevista,
        public ?string $fechaFinPrevista,
        public ?string $carpetaDriveUrl,
        public DatosPresupuesto $principal,
        public array $adicionales,
        public array $contactos,
    ) {}
}
