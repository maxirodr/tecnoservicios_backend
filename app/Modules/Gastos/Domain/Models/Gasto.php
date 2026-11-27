<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Models;

use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Enums\TipoDocumento;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Un gasto o factura, cargado una vez (gastos.md §4.1). El total es la suma de sus renglones (ADR-007).
 * Se anula en lugar de borrarse (RN-GAS-40/41). `updated_at` es la versión (409, RN-GAS-39).
 *
 * @property int $id
 * @property TipoDocumento $tipo_documento
 * @property Origen $origen
 * @property string|null $numero
 * @property Carbon $fecha
 * @property int $proveedor_id
 * @property string|null $rendido_por
 * @property Carbon|null $periodo_desde
 * @property Carbon|null $periodo_hasta
 * @property int $cargado_por
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $anulado_at
 * @property int|null $anulado_por
 * @property string|null $motivo_anulacion
 * @property Carbon|null $pagado_at
 * @property int|null $pagado_por
 * @property-read Collection<int, GastoRenglon> $renglones
 * @property-read Proveedor $proveedor
 * @property-read User $cargadoPor
 * @property-read User|null $editadoPor
 * @property-read User|null $anuladoPor
 * @property-read User|null $pagadoPor
 * @property-read Collection<int, Adjunto> $adjuntos
 */
class Gasto extends Model
{
    public const MORPH = 'gasto';

    /** Factura escaneada o tickets (identidad.md §6.6), 0 a 10. */
    public const CONTEXTO_COMPROBANTE = 'comprobante_gasto';

    /** El remito de un retiro, 0 a 1. */
    public const CONTEXTO_REMITO = 'remito';

    protected $table = 'gastos';

    protected $fillable = [
        'tipo_documento', 'origen', 'numero', 'fecha', 'proveedor_id', 'rendido_por', 'periodo_desde',
        'periodo_hasta', 'cargado_por', 'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_documento' => TipoDocumento::class,
            'origen' => Origen::class,
            'fecha' => 'date',
            'periodo_desde' => 'date',
            'periodo_hasta' => 'date',
            'proveedor_id' => 'integer',
            'cargado_por' => 'integer',
            'updated_by' => 'integer',
            'anulado_por' => 'integer',
            'anulado_at' => 'datetime',
            'pagado_at' => 'date',
            'pagado_por' => 'integer',
        ];
    }

    public function anulado(): bool
    {
        return $this->anulado_at !== null;
    }

    /** Solo las facturas tienen estado de pago (DU-C03, RN-GAS-58). */
    public function tieneEstadoDePago(): bool
    {
        return $this->tipo_documento === TipoDocumento::Factura;
    }

    public function impaga(): bool
    {
        return $this->tieneEstadoDePago() && $this->pagado_at === null;
    }

    /** El contexto de sus adjuntos: el remito en un retiro, el comprobante en facturas y W-35; el resto, ninguno. */
    public function contextoAdjuntos(): ?string
    {
        return self::contextoDe($this->origen);
    }

    public static function contextoDe(Origen $origen): ?string
    {
        return match (true) {
            $origen === Origen::RetiroRemito => self::CONTEXTO_REMITO,
            $origen === Origen::Factura, $origen->esSinDocumentoDeW35(), $origen === Origen::ParteHoras => self::CONTEXTO_COMPROBANTE,
            default => null,
        };
    }

    /**
     * Obras de sus renglones, sin "sin obra" y sin repetir.
     *
     * @return list<int>
     */
    public function obraIds(): array
    {
        $ids = [];

        foreach ($this->loadMissing('renglones')->renglones as $renglon) {
            if ($renglon->obra_id !== null) {
                $ids[$renglon->obra_id] = $renglon->obra_id;
            }
        }

        return array_values($ids);
    }

    /**
     * @return HasMany<GastoRenglon, $this>
     */
    public function renglones(): HasMany
    {
        return $this->hasMany(GastoRenglon::class, 'gasto_id')->orderBy('orden')->orderBy('id');
    }

    /**
     * @return BelongsTo<Proveedor, $this>
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cargadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cargado_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pagadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    /**
     * @return MorphMany<Adjunto, $this>
     */
    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'sujeto')->orderBy('id');
    }
}
