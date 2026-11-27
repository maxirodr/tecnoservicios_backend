<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Contracts;

use App\Shared\CargaInicial\CargaRechazada;

/**
 * Lista de materiales de una obra (F-66, W-48), para que Gastos cargue retiros y renglones con material
 * (rendicion.md §12.4.3). Rendicion lo implementa (`MaterialesDeObraEloquent`).
 */
interface MaterialesDeObra
{
    /**
     * Materiales de la obra (de la lista y fuera de presupuesto), filtrados por nombre si hay texto.
     *
     * @return list<MaterialOpcion>
     */
    public function opciones(int $obraId, ?string $texto): array;

    public function perteneceAObra(int $materialId, int $obraId): bool;

    /**
     * Crea un material "fuera de presupuesto" en la obra, dentro de la transacción de Gastos. Si ya existe
     * por nombre normalizado en esa obra, devuelve ese id sin crear otro.
     */
    public function crearFueraDePresupuesto(int $obraId, string $nombre, string $unidad, int $userId): int;

    /**
     * Carga inicial (`importacion.md` RN-IMP-28): agrega el material a la lista de la obra con las validaciones de
     * W-48. Si ya está por nombre normalizado, devuelve ese sin tocarlo (con su cantidad, para avisar si difiere).
     *
     * @throws CargaRechazada
     */
    public function agregarALaLista(int $actorId, int $obraId, MaterialInicial $material): MaterialAgregado;
}
