<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Acceso;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Adjuntos\AutorizaAdjuntos;
use Illuminate\Database\Eloquent\Model;

/**
 * Los adjuntos de la obra (oferta técnica, documento de aprobación, certificados y documentos)
 * heredan el permiso de la obra (obras.md §7, identidad.md §6.6): bajarlos exige `obras ≥ ver` con
 * su alcance; quitarlos, `obras = editar`. Registrado para `presupuesto`, `aprobacion` y `documento_obra`.
 */
final readonly class ObraAdjuntos implements AutorizaAdjuntos
{
    public function __construct(private AccesoObra $acceso) {}

    public function puedeVer(User $user, Model $sujeto): bool
    {
        $obra = self::obraDe($sujeto);

        return $obra !== null && $this->acceso->verObra($user, $obra);
    }

    public function puedeEditar(User $user, Model $sujeto): bool
    {
        $obra = self::obraDe($sujeto);

        return $obra !== null && $this->acceso->editarObra($user, $obra);
    }

    private static function obraDe(Model $sujeto): ?Obra
    {
        if (! $sujeto instanceof Presupuesto && ! $sujeto instanceof ObraDocumento) {
            return null;
        }

        return $sujeto->loadMissing('obra')->obra;
    }
}
