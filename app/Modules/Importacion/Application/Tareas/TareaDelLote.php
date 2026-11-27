<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Tareas;

use App\Modules\Importacion\Domain\Informe\Aviso;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Obras\Contracts\DocumentoInicial;
use App\Modules\Pedidos\Contracts\TareaInicial;

/**
 * Una fila de la hoja Tareas ya leída (§5.6): una tarea abierta (RN-IMP-32…37) o un documento de la obra (RN-IMP-38),
 * con todos sus faltantes y el sector que los completa (RN-IMP-33, "Para completar por sector").
 */
final class TareaDelLote
{
    public const TAREA = 'tarea';

    public const DOCUMENTO = 'documento';

    /** @var list<Faltante> */
    public array $faltantes = [];

    /** @var list<Aviso> */
    public array $avisos = [];

    public ?TareaInicial $tarea = null;

    public ?DocumentoInicial $documento = null;

    public ?int $obraId = null;

    public bool $completada = false;

    public bool $yaImportada = false;

    /** El sector del propietario, el de la columna Sector o Administración (RN-IMP-33). */
    public string $sector;

    public function __construct(
        public readonly string $clave,
        public readonly int $fila,
        public readonly ?string $tipo,
        public readonly string $obraEscrita,
        public readonly string $texto,
        string $sector,
    ) {
        $this->sector = $sector;
    }

    public function entra(): bool
    {
        return $this->faltantes === [] && ! $this->completada && ! $this->yaImportada;
    }
}
