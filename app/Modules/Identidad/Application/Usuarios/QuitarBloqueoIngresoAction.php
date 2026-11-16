<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\BloqueosDeIngreso;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;

/**
 * PU-045 · DU-Q06: quien edita usuarios destraba el ingreso de una persona bloqueada por códigos
 * fallidos (SEG-01) sin esperar la hora (o las 24 h). Queda en la actividad del usuario. Si no estaba
 * bloqueada, no hace nada (idempotente). El pedido de código sigue sin revelar el bloqueo (ADR-015).
 */
final readonly class QuitarBloqueoIngresoAction
{
    public const ACCION = 'bloqueo_ingreso_quitado';

    public function __construct(
        private BloqueosDeIngreso $bloqueos,
        private RegistroActividad $actividad,
    ) {}

    public function handle(User $usuario): void
    {
        DB::transaction(function () use ($usuario): void {
            $hasta = $this->bloqueos->quitarActivo($usuario);

            if ($hasta !== null) {
                $this->actividad->registrar($usuario, self::ACCION, [
                    'bloqueo_ingreso' => ['antes' => $hasta->toIso8601String(), 'despues' => null],
                ]);
            }
        });
    }
}
