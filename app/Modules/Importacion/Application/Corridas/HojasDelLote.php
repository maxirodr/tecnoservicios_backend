<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Identidad\Contracts\SectorConsultado;
use App\Modules\Identidad\Contracts\UsuariosConsulta;
use App\Modules\Importacion\Application\RubrosDelSistema;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Plantilla\Hoja;
use App\Modules\Importacion\Domain\Plantilla\Plantillas;

/** Las hojas de cada lote con los rubros y los sectores activos del sistema (§5, listas desplegables). */
final readonly class HojasDelLote
{
    public function __construct(
        private RubrosDelSistema $rubros,
        private UsuariosConsulta $usuarios,
    ) {}

    /**
     * @return list<Hoja>
     */
    public function de(Lote $lote): array
    {
        return Plantillas::de(
            $lote,
            $this->rubros->nombres(),
            array_keys($this->rubros->presupuestables()),
            array_map(static fn (SectorConsultado $s): string => $s->nombre, $this->usuarios->sectoresActivos()),
        );
    }
}
