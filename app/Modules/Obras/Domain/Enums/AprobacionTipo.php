<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Enums;

/** Cómo aprobó el cliente un presupuesto (obras.md §4.3). Sin tipo pero con número o adjunto: OC (RN-OBR-16). */
enum AprobacionTipo: string
{
    case Oc = 'oc';
    case Ot = 'ot';
    case Contrato = 'contrato';
    case Mail = 'mail';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Oc => 'Orden de compra',
            self::Ot => 'Orden de trabajo',
            self::Contrato => 'Contrato',
            self::Mail => 'Mail de aprobación',
        };
    }
}
