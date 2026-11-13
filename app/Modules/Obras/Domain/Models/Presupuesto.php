<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Models;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\AprobacionTipo;
use App\Modules\Obras\Domain\Enums\ModoCarga;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Presupuesto de la obra: principal o adicional (obras.md §4.2). La oferta técnica (contexto
 * `presupuesto`) y el documento de aprobación (contexto `aprobacion`) son adjuntos (ADR-014).
 *
 * @property int $id
 * @property int $obra_id
 * @property string $numero
 * @property string|null $revision
 * @property bool $es_principal
 * @property string|null $descripcion
 * @property AprobacionTipo|null $aprobacion_tipo
 * @property string|null $aprobacion_numero
 * @property ModoCarga $modo_carga
 * @property string|null $monto_obra
 * @property int $created_by
 * @property Carbon $created_at
 * @property-read Obra $obra
 * @property-read User $creadoPor
 * @property-read Collection<int, Adjunto> $adjuntos
 * @property-read Collection<int, PresupuestoMonto> $montos
 */
class Presupuesto extends Model
{
    public const MORPH = 'presupuesto';

    public const CONTEXTO_OFERTA = 'presupuesto';

    public const CONTEXTO_APROBACION = 'aprobacion';

    protected $table = 'presupuestos';

    protected $fillable = [
        'obra_id', 'numero', 'revision', 'es_principal', 'descripcion', 'aprobacion_tipo',
        'aprobacion_numero', 'modo_carga', 'monto_obra', 'created_by',
    ];

    protected $attributes = ['modo_carga' => 'sin_cargar'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obra_id' => 'integer',
            'es_principal' => 'boolean',
            'aprobacion_tipo' => AprobacionTipo::class,
            'modo_carga' => ModoCarga::class,
            'monto_obra' => 'decimal:2',
            'created_by' => 'integer',
        ];
    }

    /** "2-131-2026 REV01" o "2-131-2026 A1" (W-13, historial). */
    public function etiqueta(): string
    {
        return $this->revision === null ? $this->numero : "{$this->numero} {$this->revision}";
    }

    public function oferta(): ?Adjunto
    {
        return $this->adjuntos->firstWhere('contexto', self::CONTEXTO_OFERTA);
    }

    public function adjuntoAprobacion(): ?Adjunto
    {
        return $this->adjuntos->firstWhere('contexto', self::CONTEXTO_APROBACION);
    }

    /**
     * @return BelongsTo<Obra, $this>
     */
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<Adjunto, $this>
     */
    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'sujeto')->orderBy('id');
    }

    /**
     * @return HasMany<PresupuestoMonto, $this>
     */
    public function montos(): HasMany
    {
        return $this->hasMany(PresupuestoMonto::class, 'presupuesto_id');
    }
}
