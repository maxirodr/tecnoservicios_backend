<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Domain\Models\Material;

/**
 * Material del renglón (RN-GAS-14, 24, 25; `rendicion.md` §12.4.1 invariantes 4 y 8): de la lista de
 * **esa** obra (`MaterialesDeObra::perteneceAObra`) o "No está en la lista" (nombre y unidad: lo crea
 * Rendicion como fuera de presupuesto al guardar). En un retiro es obligatorio; en factura y devolución,
 * opcional; las demás cargas no llevan material.
 */
final readonly class MaterialDelRenglon
{
    public const ELEGI_EL_MATERIAL = "Elegí el material o 'No está en la lista'.";

    public function __construct(private MaterialesDeObra $materiales) {}

    public static function deOtraObra(string $nombre): string
    {
        return "El material {$nombre} es de otra obra: quitá el material o elegí uno de esta obra.";
    }

    /**
     * @return array{0: int|null, 1: MaterialNuevo|null}
     */
    public function resolver(Origen $origen, DatosRenglon $renglon, Errores $errores): array
    {
        if (! in_array($origen, [Origen::Factura, Origen::RetiroRemito, Origen::Devolucion], true)) {
            return [null, null];
        }

        $ninguno = $renglon->materialId === null && $renglon->materialNuevo === null;
        $ambos = $renglon->materialId !== null && $renglon->materialNuevo !== null;

        if ($ambos || ($ninguno && $origen === Origen::RetiroRemito)) {
            $errores->renglon($renglon->posicion, 'material_id', self::ELEGI_EL_MATERIAL);

            return [null, null];
        }

        if ($renglon->materialNuevo !== null) {
            if ($renglon->obraId === null) {
                $errores->renglon($renglon->posicion, 'material_id', self::deOtraObra($renglon->materialNuevo->nombre));
            }

            return [null, $renglon->materialNuevo];
        }

        if ($renglon->materialId !== null) {
            $this->verificarDeLaObra($renglon->materialId, $renglon, $errores);
        }

        return [$renglon->materialId, null];
    }

    /** Al cargar y al corregir la obra de un renglón que tiene material (RN-GAS-14). */
    public function verificarDeLaObra(int $materialId, DatosRenglon $renglon, Errores $errores): void
    {
        if ($renglon->obraId !== null && $this->materiales->perteneceAObra($materialId, $renglon->obraId)) {
            return;
        }

        $nombre = Material::query()->whereKey($materialId)->value('nombre');

        $errores->renglon(
            $renglon->posicion,
            'material_id',
            is_string($nombre) ? self::deOtraObra($nombre) : self::ELEGI_EL_MATERIAL,
        );
    }
}
