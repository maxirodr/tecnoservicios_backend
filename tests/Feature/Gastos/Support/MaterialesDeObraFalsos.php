<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos\Support;

use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Contracts\MaterialOpcion;
use App\Modules\Rendicion\Domain\Models\Material;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;

/**
 * Lo que implementa Rendicion (rendicion.md §12.4.3), del otro lado del contrato: sobre la tabla
 * `materiales` (los renglones la referencian por FK), anotando los fuera de presupuesto que se crean.
 */
final class MaterialesDeObraFalsos implements MaterialesDeObra
{
    /** @var list<array{obra: int, nombre: string, unidad: string}> */
    public array $creados = [];

    public static function registrar(Container $app): self
    {
        $falsos = new self;
        $app->instance(MaterialesDeObra::class, $falsos);

        return $falsos;
    }

    public function opciones(int $obraId, ?string $texto): array
    {
        return array_values(Material::query()->where('obra_id', $obraId)->orderBy('id')->get()
            ->map(static fn (Material $m): MaterialOpcion => new MaterialOpcion($m->id, $m->nombre, $m->unidad, $m->fuera_de_presupuesto, null, null))
            ->all());
    }

    public function perteneceAObra(int $materialId, int $obraId): bool
    {
        return Material::query()->whereKey($materialId)->where('obra_id', $obraId)->exists();
    }

    public function crearFueraDePresupuesto(int $obraId, string $nombre, string $unidad, int $userId): int
    {
        $clave = Str::of($nombre)->ascii()->lower()->squish()->toString();
        $existente = Material::query()->where('obra_id', $obraId)->where('nombre_clave', $clave)->value('id');

        if (is_numeric($existente)) {
            return (int) $existente;
        }

        $this->creados[] = ['obra' => $obraId, 'nombre' => $nombre, 'unidad' => $unidad];

        return Material::query()->create([
            'obra_id' => $obraId, 'nombre' => $nombre, 'nombre_clave' => $clave, 'unidad' => $unidad,
            'fuera_de_presupuesto' => true, 'created_by' => $userId, 'updated_by' => $userId,
        ])->id;
    }
}
