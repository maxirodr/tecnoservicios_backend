<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Bloqueo progresivo del ingreso por código (SEG-01, enmienda 2026-11-13 de ADR-015). Un código está
 * "quemado" cuando se invalida por los 5 intentos fallidos. 3 quemados seguidos, sin un ingreso
 * exitoso en el medio, bloquean el mail 1 hora; otros 3 después de cumplir un bloqueo, 24 horas.
 * Un ingreso exitoso borra la fila (contador en cero).
 *
 * @property int $id
 * @property int $user_id
 * @property int $codigos_quemados
 * @property int $bloqueos_cumplidos
 * @property Carbon|null $bloqueado_hasta
 */
final class BloqueoIngreso extends Model
{
    public const QUEMADOS_PARA_BLOQUEAR = 3;

    public const HORAS_PRIMER_BLOQUEO = 1;

    public const HORAS_REINCIDENCIA = 24;

    protected $table = 'bloqueos_ingreso';

    protected $fillable = ['user_id', 'codigos_quemados', 'bloqueos_cumplidos', 'bloqueado_hasta'];

    protected $attributes = ['codigos_quemados' => 0, 'bloqueos_cumplidos' => 0];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'codigos_quemados' => 'integer',
            'bloqueos_cumplidos' => 'integer',
            'bloqueado_hasta' => 'datetime',
        ];
    }

    public function bloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    /** Suma un código quemado; al llegar al umbral bloquea (1 h la primera vez, 24 h si reincide) y vuelve a contar desde cero. */
    public function registrarQuemado(): void
    {
        $this->codigos_quemados++;

        if ($this->codigos_quemados < self::QUEMADOS_PARA_BLOQUEAR) {
            return;
        }

        $horas = $this->bloqueos_cumplidos === 0 ? self::HORAS_PRIMER_BLOQUEO : self::HORAS_REINCIDENCIA;
        $this->bloqueado_hasta = Carbon::now()->addHours($horas);
        $this->bloqueos_cumplidos++;
        $this->codigos_quemados = 0;
    }
}
