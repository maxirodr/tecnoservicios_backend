<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain;

/** Los dos lotes de la carga inicial, en este orden (RN-IMP-02). */
enum Lote: string
{
    case Obras = 'obras';
    case Tareas = 'tareas';
}
