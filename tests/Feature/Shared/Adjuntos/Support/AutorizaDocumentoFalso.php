<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos\Support;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\AutorizaAdjuntos;
use Illuminate\Database\Eloquent\Model;

/**
 * Como lo haría Obras: función `obras` de la matriz, con alcance "solo sus obras" = donde es responsable.
 */
final readonly class AutorizaDocumentoFalso implements AutorizaAdjuntos
{
    public const FUNCION = 'obras';

    public function __construct(private Permisos $permisos) {}

    public function puedeVer(User $user, Model $sujeto): bool
    {
        return $this->puede($user, $sujeto, Nivel::Ver);
    }

    public function puedeEditar(User $user, Model $sujeto): bool
    {
        return $this->puede($user, $sujeto, Nivel::Editar);
    }

    private function puede(User $user, Model $sujeto, Nivel $nivel): bool
    {
        if (! $sujeto instanceof DocumentoFalso || ! $this->permisos->puede($user, self::FUNCION, $nivel)) {
            return false;
        }

        return $this->permisos->alcance($user, self::FUNCION) === Alcance::Todas
            || $sujeto->responsable_id === $user->getKey();
    }
}
