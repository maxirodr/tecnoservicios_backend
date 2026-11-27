<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Plantilla;

/**
 * Una hoja de las plantillas (`importacion.md` §5): su nombre, sus columnas en el orden de la plantilla y cuáles son
 * obligatorias como columna (las que la spec marca "**Sí**", también "Sí si…"). Sin una hoja obligatoria o sin una
 * columna obligatoria de una hoja que vino, la corrida no empieza (RN-IMP-11).
 */
final readonly class Hoja
{
    /**
     * @param  list<string>  $columnas
     * @param  list<string>  $obligatorias
     * @param  array<string, list<string>>  $listas  columna => valores de la lista desplegable
     */
    public function __construct(
        public string $nombre,
        public bool $obligatoria,
        public array $columnas,
        public array $obligatorias,
        public array $listas = [],
    ) {}
}
