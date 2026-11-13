<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lo presupuestado de un rubro en un presupuesto (F-48). `rubro_id` null = "solo el total" (C16).
 *
 * @property int $id
 * @property int $presupuesto_id
 * @property int|null $rubro_id
 * @property string $monto
 */
class PresupuestoMonto extends Model
{
    protected $table = 'presupuesto_montos';

    protected $fillable = ['presupuesto_id', 'rubro_id', 'monto'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'presupuesto_id' => 'integer',
            'rubro_id' => 'integer',
            'monto' => 'decimal:2',
        ];
    }
}
