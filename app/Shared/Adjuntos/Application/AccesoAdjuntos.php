<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\AutorizaAdjuntos;
use App\Shared\Adjuntos\ContextosAdjuntos;
use Illuminate\Database\Eloquent\Model;

/**
 * Quién ve y quién edita un adjunto (identidad.md §6.6): un temporal, solo quien lo subió;
 * uno asociado, quien puede ver / editar el recurso dueño según el módulo que registró su contexto.
 */
final readonly class AccesoAdjuntos
{
    public function __construct(private ContextosAdjuntos $contextos) {}

    public function puedeVer(User $user, Adjunto $adjunto): bool
    {
        return $this->decidir($user, $adjunto, static fn (AutorizaAdjuntos $a, Model $s): bool => $a->puedeVer($user, $s));
    }

    public function puedeEditar(User $user, Adjunto $adjunto): bool
    {
        return $this->decidir($user, $adjunto, static fn (AutorizaAdjuntos $a, Model $s): bool => $a->puedeEditar($user, $s));
    }

    /**
     * @param  callable(AutorizaAdjuntos, Model): bool  $regla
     */
    private function decidir(User $user, Adjunto $adjunto, callable $regla): bool
    {
        if ($adjunto->esTemporal()) {
            return $adjunto->esDe($user);
        }

        $autorizador = $this->contextos->autorizador($adjunto->contexto);
        $sujeto = $adjunto->sujeto;

        return $autorizador !== null && $sujeto !== null && $regla($autorizador, $sujeto);
    }
}
