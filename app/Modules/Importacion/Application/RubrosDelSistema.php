<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application;

use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;

/**
 * La lista cerrada de rubros (cap. 9) por su nombre, para la hoja Rubros (13) y las columnas de la hoja Presupuestos
 * (12, sin EPP): `CatalogoDeRubros` de Rendicion (RN-IMP-16).
 */
final class RubrosDelSistema
{
    /** @var list<RubroDelCatalogo>|null */
    private ?array $imputables = null;

    public function __construct(private readonly CatalogoDeRubros $catalogo) {}

    /**
     * @return list<string> los 13 nombres, en el orden de su hoja General
     */
    public function nombres(): array
    {
        return array_map(static fn (RubroDelCatalogo $r): string => $r->nombre, $this->imputables());
    }

    /**
     * @return array<string, string> nombre => clave de los 12 presupuestables
     */
    public function presupuestables(): array
    {
        $porNombre = [];

        foreach ($this->catalogo->presupuestables() as $rubro) {
            $porNombre[$rubro->nombre] = $rubro->clave;
        }

        return $porNombre;
    }

    /** La clave del rubro cuyo nombre es este, sin contar mayúsculas ni espacios al borde (RN-IMP-16: "insumos "). */
    public function claveDe(string $escrito): ?string
    {
        foreach ($this->imputables() as $rubro) {
            if (mb_strtolower(trim($escrito)) === mb_strtolower($rubro->nombre)) {
                return $rubro->clave;
            }
        }

        return null;
    }

    public function nombreDe(string $clave): string
    {
        foreach ($this->imputables() as $rubro) {
            if ($rubro->clave === $clave) {
                return $rubro->nombre;
            }
        }

        return $clave;
    }

    /**
     * @return list<RubroDelCatalogo>
     */
    private function imputables(): array
    {
        return $this->imputables ??= $this->catalogo->imputables();
    }
}
