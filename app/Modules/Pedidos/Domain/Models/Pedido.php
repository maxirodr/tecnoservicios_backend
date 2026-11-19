<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Models;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Una fila de la minuta (pedidos.md §4.1). El estado cambia solo con `PedidoStateMachine`; no se borra
 * (RN-PED-30). `propietario_id` null = "en el sector" (resuelta DU-16).
 *
 * @property int $id
 * @property int|null $obra_id
 * @property string $tarea
 * @property int $sector_id
 * @property int|null $propietario_id
 * @property int|null $solicitante_id
 * @property string|null $con_quien
 * @property Carbon|null $fecha_pedido
 * @property Carbon|null $fecha_entrega
 * @property Carbon|null $periodo_inicio
 * @property Carbon|null $periodo_fin
 * @property string|null $aclaraciones
 * @property Prioridad $prioridad
 * @property bool $prioridad_manual
 * @property PedidoEstado $estado
 * @property string|null $motivo_bloqueo
 * @property string|null $comentario_cierre
 * @property Carbon|null $completado_at
 * @property int|null $completado_por
 * @property bool $carga_inicial
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Obra|null $obra
 * @property-read Sector $sector
 * @property-read User|null $propietario
 * @property-read User|null $solicitante
 * @property-read User|null $completadoPor
 * @property-read Collection<int, PedidoComentario> $comentarios
 * @property-read Collection<int, User> $seguidores
 * @property-read Collection<int, Adjunto> $adjuntos
 * @property-read Collection<int, Adjunto> $comprobantes
 */
class Pedido extends Model
{
    public const MORPH = 'pedido';

    public const CONTEXTO_ADJUNTOS = 'pedido';

    public const CONTEXTO_COMPROBANTES = 'comprobante_cierre';

    protected $table = 'pedidos';

    protected $fillable = [
        'obra_id', 'tarea', 'sector_id', 'propietario_id', 'solicitante_id', 'con_quien', 'fecha_pedido',
        'fecha_entrega', 'periodo_inicio', 'periodo_fin', 'aclaraciones', 'prioridad', 'prioridad_manual', 'estado',
        'carga_inicial',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obra_id' => 'integer',
            'sector_id' => 'integer',
            'propietario_id' => 'integer',
            'solicitante_id' => 'integer',
            'completado_por' => 'integer',
            'fecha_pedido' => 'date',
            'fecha_entrega' => 'date',
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'prioridad' => Prioridad::class,
            'prioridad_manual' => 'boolean',
            'estado' => PedidoEstado::class,
            'completado_at' => 'datetime',
            'carga_inicial' => 'boolean',
        ];
    }

    public function enElSector(): bool
    {
        return $this->propietario_id === null;
    }

    public function esPropietario(User $user): bool
    {
        return $this->propietario_id !== null && $this->propietario_id === $user->id;
    }

    public function esSolicitante(User $user): bool
    {
        return $this->solicitante_id !== null && $this->solicitante_id === $user->id;
    }

    /** Pedido en el sector y la persona es activa de ese sector: actúa como propietaria (DU-16, DU-P02). */
    public function esDelSectorSinPropietario(User $user): bool
    {
        return $this->enElSector() && $user->activo && $user->sector_id === $this->sector_id;
    }

    /** Vencido = abierto con la entrega antes de hoy (RN-PED-36); `$hoy` en la zona de la empresa. */
    public function vencidoAl(string $hoy): bool
    {
        return $this->estado->abierto() && $this->fecha_entrega !== null && $this->fecha_entrega->toDateString() < $hoy;
    }

    public function venceElDia(string $dia): bool
    {
        return $this->estado->abierto() && $this->fecha_entrega?->toDateString() === $dia;
    }

    /**
     * @return BelongsTo<Obra, $this>
     */
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'propietario_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completado_por');
    }

    /**
     * Del más viejo al más nuevo (RN-PED-25).
     *
     * @return HasMany<PedidoComentario, $this>
     */
    public function comentarios(): HasMany
    {
        return $this->hasMany(PedidoComentario::class, 'pedido_id')->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function seguidores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pedido_seguidores', 'pedido_id', 'user_id')->orderBy('pedido_seguidores.id');
    }

    /**
     * @return MorphMany<Adjunto, $this>
     */
    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'sujeto')->where('contexto', self::CONTEXTO_ADJUNTOS)->orderBy('id');
    }

    /**
     * @return MorphMany<Adjunto, $this>
     */
    public function comprobantes(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'sujeto')->where('contexto', self::CONTEXTO_COMPROBANTES)->orderBy('id');
    }
}
