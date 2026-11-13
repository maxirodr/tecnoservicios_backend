<?php

declare(strict_types=1);

namespace App\Shared\Idempotencia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Una Idempotency-Key ya vista (ADR-011): mientras se procesa queda `en_curso`;
 * al terminar bien guarda la respuesta para devolverla igual en los reintentos.
 *
 * @property int $id
 * @property int $user_id
 * @property string $clave
 * @property string $ruta
 * @property string $payload_hash
 * @property string $status
 * @property array{status: int, content_type: string|null, body: string}|null $respuesta
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class IdempotencyKey extends Model
{
    public const EN_CURSO = 'en_curso';

    public const COMPLETADA = 'completada';

    /** Vigencia de una clave (ADR-011): pasado este plazo se purga y la clave vuelve a estar libre. */
    public const HORAS_VIGENCIA = 24;

    protected $table = 'idempotency_keys';

    protected $fillable = ['user_id', 'clave', 'ruta', 'payload_hash', 'status', 'respuesta'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'respuesta' => 'array',
        ];
    }

    public function vencida(): bool
    {
        return $this->created_at->lte(Carbon::now()->subHours(self::HORAS_VIGENCIA));
    }

    public function coincideCon(string $ruta, string $payloadHash): bool
    {
        return $this->ruta === $ruta && hash_equals($this->payload_hash, $payloadHash);
    }
}
