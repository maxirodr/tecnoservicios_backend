<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Infrastructure;

use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;
use App\Modules\Rendicion\Domain\Models\Rubro;

/** Lee el catálogo de la migración `2026_11_13_300001` (13 filas: se carga una vez por instancia). */
final class CatalogoDeRubrosEloquent implements CatalogoDeRubros
{
    /** @var list<Rubro>|null */
    private ?array $rubros = null;

    public function imputables(): array
    {
        return array_map(self::aCatalogo(...), $this->rubros());
    }

    public function presupuestables(): array
    {
        return array_values(array_map(
            self::aCatalogo(...),
            array_filter($this->rubros(), static fn (Rubro $r): bool => $r->se_presupuesta),
        ));
    }

    public function resumeEn(string $clave): ?string
    {
        $porId = [];
        foreach ($this->rubros() as $rubro) {
            $porId[$rubro->id] = $rubro;
        }

        foreach ($this->rubros() as $rubro) {
            if ($rubro->clave === $clave) {
                return $rubro->resume_en_rubro_id === null ? null : ($porId[$rubro->resume_en_rubro_id]->clave ?? null);
            }
        }

        return null;
    }

    /**
     * @return list<Rubro>
     */
    private function rubros(): array
    {
        return $this->rubros ??= array_values(Rubro::query()->orderBy('orden')->get()->all());
    }

    private static function aCatalogo(Rubro $rubro): RubroDelCatalogo
    {
        return new RubroDelCatalogo($rubro->id, $rubro->clave, $rubro->nombre, $rubro->carga_por_periodo);
    }
}
