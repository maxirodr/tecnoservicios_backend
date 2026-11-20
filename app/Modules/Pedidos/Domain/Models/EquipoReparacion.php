<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Models;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Enums\EquipoAprobacion;
use App\Modules\Pedidos\Domain\Enums\EquipoEstado;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * Un equipo en un taller (pedidos.md §4.4, F-67, W-49). Estado y aprobación se cambian libremente; se
 * quita solo mientras sigue No iniciada (resuelta DU-P18). Inventario y presupuesto: un adjunto cada uno.
 *
 * @property int $id
 * @property string $equipo
 * @property string|null $codigo
 * @property string $trabajo
 * @property string|null $taller
 * @property EquipoEstado $estado
 * @property EquipoAprobacion $aprobacion
 * @property Carbon|null $fecha
 * @property string|null $notas
 * @property int $created_by
 * @property int $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $actualizadoPor
 * @property-read Adjunto|null $inventario
 * @property-read Adjunto|null $presupuesto
 */
class EquipoReparacion extends Model
{
    public const MORPH = 'equipo_reparacion';

    public const CONTEXTO_INVENTARIO = 'equipo_inventario';

    public const CONTEXTO_PRESUPUESTO = 'equipo_presupuesto';

    protected $table = 'equipos_reparacion';

    protected $fillable = ['equipo', 'codigo', 'trabajo', 'taller', 'estado', 'aprobacion', 'fecha', 'notas'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EquipoEstado::class,
            'aprobacion' => EquipoAprobacion::class,
            'fecha' => 'date',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return MorphOne<Adjunto, $this>
     */
    public function inventario(): MorphOne
    {
        return $this->morphOne(Adjunto::class, 'sujeto')->where('contexto', self::CONTEXTO_INVENTARIO);
    }

    /**
     * @return MorphOne<Adjunto, $this>
     */
    public function presupuesto(): MorphOne
    {
        return $this->morphOne(Adjunto::class, 'sujeto')->where('contexto', self::CONTEXTO_PRESUPUESTO);
    }
}
