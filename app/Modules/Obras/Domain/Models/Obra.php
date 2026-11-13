<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Models;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use Database\Factories\ObraFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Obra / legajo (obras.md §4.1). El estado se cambia solo con `ObraStateMachine` (W-22).
 *
 * @property int $id
 * @property string $nombre_trabajo
 * @property string $cliente
 * @property string|null $planta
 * @property int $responsable_id
 * @property ObraEstado $estado
 * @property Carbon|null $fecha_inicio_prevista
 * @property Carbon|null $fecha_fin_prevista
 * @property string|null $carpeta_drive_url
 * @property \DateTimeInterface|null $terminada_at
 * @property int|null $terminada_por
 * @property int $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $responsable
 * @property-read User|null $terminadaPor
 * @property-read User $creadaPor
 * @property-read Presupuesto|null $principal
 * @property-read Collection<int, Presupuesto> $presupuestos
 * @property-read Collection<int, ObraContacto> $contactos
 * @property-read Collection<int, ObraDocumento> $documentos
 */
#[UseFactory(ObraFactory::class)]
class Obra extends Model
{
    /** @use HasFactory<ObraFactory> */
    use HasFactory;

    public const MORPH = 'obra';

    protected $table = 'obras';

    protected $fillable = [
        'nombre_trabajo', 'cliente', 'planta', 'responsable_id', 'estado', 'fecha_inicio_prevista',
        'fecha_fin_prevista', 'carpeta_drive_url', 'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => ObraEstado::class,
            'responsable_id' => 'integer',
            'terminada_por' => 'integer',
            'created_by' => 'integer',
            'fecha_inicio_prevista' => 'date',
            'fecha_fin_prevista' => 'date',
            'terminada_at' => 'datetime',
        ];
    }

    public function esResponsable(User $user): bool
    {
        return $this->responsable_id === (int) $user->getKey();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function terminadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminada_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Principal y adicionales, en orden de alta (HU-015 criterio 2).
     *
     * @return HasMany<Presupuesto, $this>
     */
    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class, 'obra_id')->orderByDesc('es_principal')->orderBy('id');
    }

    /**
     * @return HasOne<Presupuesto, $this>
     */
    public function principal(): HasOne
    {
        return $this->hasOne(Presupuesto::class, 'obra_id')->where('es_principal', true);
    }

    /**
     * @return HasMany<ObraContacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(ObraContacto::class, 'obra_id');
    }

    /**
     * @return HasMany<ObraDocumento, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(ObraDocumento::class, 'obra_id')->orderBy('id');
    }
}
