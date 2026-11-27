<?php

declare(strict_types=1);

namespace App\Shared\Actividad;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Escribe en `actividades` con el usuario autenticado (null si lo hace el sistema, p. ej. un comando).
 * Durante la carga inicial, a nombre de quien aprobó y con la marca `carga_inicial` (RN-IMP-39).
 * Usa la conexión por defecto: queda dentro de la transacción del caso de uso que la llama.
 * El guard se resuelve en cada llamada (el middleware `auth:sanctum` cambia el guard por defecto).
 */
final readonly class EloquentRegistroActividad implements RegistroActividad
{
    public function __construct(
        private AuthFactory $auth,
        private CargaInicialEnCurso $cargaInicial,
    ) {}

    public function registrar(Model $sujeto, string $accion, array $cambios = []): Actividad
    {
        $userId = $this->cargaInicial->actorId() ?? $this->auth->guard()->id();

        return Actividad::query()->create([
            'sujeto_type' => $sujeto->getMorphClass(),
            'sujeto_id' => $sujeto->getKey(),
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'accion' => $accion,
            'cambios' => $cambios === [] ? null : $cambios,
            'carga_inicial' => $this->cargaInicial->activa(),
        ]);
    }
}
