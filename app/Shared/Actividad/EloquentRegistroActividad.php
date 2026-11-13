<?php

declare(strict_types=1);

namespace App\Shared\Actividad;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Escribe en `actividades` con el usuario autenticado (null si lo hace el sistema, p. ej. un comando).
 * Usa la conexión por defecto: queda dentro de la transacción del caso de uso que la llama.
 * El guard se resuelve en cada llamada (el middleware `auth:sanctum` cambia el guard por defecto).
 */
final readonly class EloquentRegistroActividad implements RegistroActividad
{
    public function __construct(private AuthFactory $auth) {}

    public function registrar(Model $sujeto, string $accion, array $cambios = []): void
    {
        $userId = $this->auth->guard()->id();

        Actividad::query()->create([
            'sujeto_type' => $sujeto->getMorphClass(),
            'sujeto_id' => $sujeto->getKey(),
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'accion' => $accion,
            'cambios' => $cambios === [] ? null : $cambios,
        ]);
    }
}
