<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Models;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Rendicion\Domain\Models\Material;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un renglón: lo único que suma (ADR-007). `fecha` es la fecha efectiva (resuelta DU-G01). `total` lo
 * calcula el backend (`TotalDeRenglon`). `obra_id` null = "sin obra". El rubro se lee por su id con
 * `CatalogoDeRubros` (gastos.md §12.4).
 *
 * @property int $id
 * @property int $gasto_id
 * @property int $orden
 * @property Carbon $fecha
 * @property string $concepto
 * @property numeric-string $cantidad
 * @property numeric-string $precio_unitario
 * @property numeric-string $total
 * @property int|null $obra_id
 * @property int|null $rubro_id
 * @property int|null $material_id
 * @property string|null $detalle
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Gasto $gasto
 * @property-read Obra|null $obra
 * @property-read Material|null $material
 */
class GastoRenglon extends Model
{
    protected $table = 'gasto_renglones';

    protected $fillable = [
        'gasto_id', 'orden', 'fecha', 'concepto', 'cantidad', 'precio_unitario', 'total', 'obra_id', 'rubro_id',
        'material_id', 'detalle',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gasto_id' => 'integer',
            'orden' => 'integer',
            'fecha' => 'date',
            'cantidad' => 'decimal:3',
            'precio_unitario' => 'decimal:2',
            'total' => 'decimal:2',
            'obra_id' => 'integer',
            'rubro_id' => 'integer',
            'material_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Gasto, $this>
     */
    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class, 'gasto_id');
    }

    /**
     * @return BelongsTo<Obra, $this>
     */
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
