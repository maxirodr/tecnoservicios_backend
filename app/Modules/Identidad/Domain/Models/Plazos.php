<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Models;

use App\Modules\Identidad\Domain\CalculadorPrioridad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Plazos de la prioridad (F-68, W-50): una sola fila. "Normal" = más de `prioritario_hasta_dias`.
 *
 * @property int $id
 * @property int $urgente_hasta_dias
 * @property int $prioritario_hasta_dias
 * @property int|null $updated_by
 * @property Carbon|null $updated_at
 * @property-read User|null $editor
 */
class Plazos extends Model
{
    protected $table = 'plazos_prioridad';

    protected $fillable = ['urgente_hasta_dias', 'prioritario_hasta_dias', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urgente_hasta_dias' => 'integer',
            'prioritario_hasta_dias' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function calculador(): CalculadorPrioridad
    {
        return new CalculadorPrioridad($this->urgente_hasta_dias, $this->prioritario_hasta_dias);
    }
}
