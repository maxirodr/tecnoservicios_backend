<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Models;

use App\Modules\Obras\Domain\Enums\TipoDocumento;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Certificado u otro documento de la obra (obras.md §4.5), con 1 a 10 adjuntos (contexto `documento_obra`).
 *
 * @property int $id
 * @property int $obra_id
 * @property TipoDocumento $tipo
 * @property string $nombre
 * @property Carbon|null $fecha
 * @property int $created_by
 * @property Carbon $created_at
 * @property-read Obra $obra
 * @property-read Collection<int, Adjunto> $adjuntos
 */
class ObraDocumento extends Model
{
    public const MORPH = 'obra_documento';

    public const CONTEXTO = 'documento_obra';

    protected $table = 'obra_documentos';

    protected $fillable = ['obra_id', 'tipo', 'nombre', 'fecha', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obra_id' => 'integer',
            'tipo' => TipoDocumento::class,
            'fecha' => 'date',
            'created_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Obra, $this>
     */
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    /**
     * @return MorphMany<Adjunto, $this>
     */
    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'sujeto')->orderBy('id');
    }
}
