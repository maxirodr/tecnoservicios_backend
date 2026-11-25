<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Contracts\PresupuestoDeObra;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Domain\TextoComparable;
use App\Shared\Texto;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de la lista de materiales que miran la base (rendicion.md §4.2): el nombre no se repite en la obra
 * (sin distinguir mayúsculas, tildes ni espacios dobles) y el presupuesto es de esa obra; si la obra tiene un
 * solo presupuesto, se completa solo.
 */
final readonly class ReglasDeLaLista
{
    public function __construct(private ObrasConsulta $obras) {}

    /**
     * @throws ValidationException
     */
    public function nombreLibre(int $obraId, string $nombre, ?int $excepto = null): void
    {
        $existente = Material::query()
            ->where('obra_id', $obraId)
            ->where('nombre_clave', TextoComparable::claveDeMaterial($nombre))
            ->when($excepto !== null, static fn ($q) => $q->whereKeyNot($excepto))
            ->first();

        if ($existente instanceof Material) {
            throw self::repetido($existente->nombre);
        }
    }

    public static function repetido(string $nombre): ValidationException
    {
        return ValidationException::withMessages(['nombre' => [Texto::conPunto("Ya está en la lista: {$nombre}")]]);
    }

    /** El número del presupuesto de la obra ("1-200-2026"), para mostrarlo con el material. */
    public function numeroDe(int $obraId, ?int $presupuestoId): ?string
    {
        foreach ($presupuestoId === null ? [] : $this->obras->presupuestadoPorRubro($obraId)->presupuestos as $presupuesto) {
            if ($presupuesto->id === $presupuestoId) {
                return $presupuesto->numero;
            }
        }

        return null;
    }

    /**
     * @throws ValidationException si el presupuesto no es de la obra
     */
    public function presupuesto(int $obraId, ?int $presupuestoId, bool $completarSiHayUnoSolo): ?int
    {
        $ids = array_map(static fn (PresupuestoDeObra $p): int => $p->id, $this->obras->presupuestadoPorRubro($obraId)->presupuestos);

        if ($presupuestoId === null) {
            return $completarSiHayUnoSolo && count($ids) === 1 ? $ids[0] : null;
        }

        if (! in_array($presupuestoId, $ids, true)) {
            throw ValidationException::withMessages(['presupuesto_id' => ['Ese presupuesto no es de esta obra.']]);
        }

        return $presupuestoId;
    }
}
