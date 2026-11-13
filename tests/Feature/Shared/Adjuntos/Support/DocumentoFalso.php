<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Recurso dueño de prueba, con responsable (como una obra), para probar los adjuntos sin depender de Obras.
 *
 * @property int $id
 * @property int|null $responsable_id
 */
final class DocumentoFalso extends Model
{
    public const TABLA = 'documentos_falsos';

    public $timestamps = false;

    protected $table = self::TABLA;

    protected $fillable = ['responsable_id'];

    protected $casts = ['responsable_id' => 'integer'];
}
