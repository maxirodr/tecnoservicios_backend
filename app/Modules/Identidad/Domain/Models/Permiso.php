<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Models;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Celda de la matriz sector × función (ADR-006): nivel + alcance.
 *
 * @property int $id
 * @property int $sector_id
 * @property int $funcion_id
 * @property Nivel $nivel
 * @property Alcance $alcance
 * @property-read Funcion $funcion
 * @property-read Sector $sector
 */
#[Fillable(['sector_id', 'funcion_id', 'nivel', 'alcance'])]
class Permiso extends Model
{
    protected $table = 'permisos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sector_id' => 'integer',
            'funcion_id' => 'integer',
            'nivel' => Nivel::class,
            'alcance' => Alcance::class,
        ];
    }

    /**
     * @return BelongsTo<Funcion, $this>
     */
    public function funcion(): BelongsTo
    {
        return $this->belongsTo(Funcion::class, 'funcion_id');
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }
}
