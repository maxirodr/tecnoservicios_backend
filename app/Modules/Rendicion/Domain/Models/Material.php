<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Material de la lista de una obra (F-66, W-48). `updated_at` es la versión (409). Lo retirado es la suma
 * de los renglones de Gastos con este `material_id` (se lee por `GastadoDeObras`).
 *
 * @property int $id
 * @property int $obra_id
 * @property int|null $presupuesto_id
 * @property string $nombre
 * @property string $nombre_clave
 * @property string $unidad
 * @property string|null $cantidad_presupuestada
 * @property string|null $precio_presupuestado
 * @property bool $fuera_de_presupuesto
 * @property int $created_by
 * @property int $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Material extends Model
{
    public const MORPH = 'material';

    protected $table = 'materiales';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_presupuestada' => 'decimal:3',
            'precio_presupuestado' => 'decimal:2',
            'fuera_de_presupuesto' => 'boolean',
        ];
    }
}
