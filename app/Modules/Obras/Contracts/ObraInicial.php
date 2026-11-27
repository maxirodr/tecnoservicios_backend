<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/**
 * Una obra de la hoja Obras, ya leída y validada en su formato (`importacion.md` §5.1). Fechas "AAAA-MM-DD".
 * `presupuestos` empieza por el principal.
 *
 * @phpstan-type Contacto array{nombre: string, email: string, telefono: string}
 */
final readonly class ObraInicial
{
    /**
     * @param  non-empty-list<PresupuestoInicial>  $presupuestos
     * @param  array{solicitante: Contacto, comprador: Contacto}  $contactos
     */
    public function __construct(
        public string $nombreTrabajo,
        public string $cliente,
        public ?string $planta,
        public int $responsableId,
        public bool $enEjecucion,
        public ?string $fechaInicioPrevista,
        public ?string $fechaFinPrevista,
        public ?string $carpetaDriveUrl,
        public array $presupuestos,
        public array $contactos,
    ) {}
}
