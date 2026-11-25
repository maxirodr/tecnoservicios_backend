<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Contracts\SectorConsultado;
use App\Modules\Identidad\Contracts\UsuarioConsultado;
use App\Modules\Identidad\Contracts\UsuariosConsulta;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Domain\NombreClave;
use Illuminate\Database\Eloquent\Builder;

/** `UsuariosConsulta` sobre las tablas de Identidad y la matriz (`Permisos`, ADR-006). */
final readonly class UsuariosConsultaEloquent implements UsuariosConsulta
{
    public function __construct(private Permisos $permisos) {}

    public function porMail(string $mail): ?UsuarioConsultado
    {
        $user = User::query()->with('sector')->where('email', MailNormalizado::de($mail))->first();

        return $user === null ? null : self::usuario($user);
    }

    public function activos(): array
    {
        return array_values(User::query()->with('sector')->where('activo', true)->orderBy('nombre')->orderBy('id')->get()
            ->map(self::usuario(...))->all());
    }

    public function sectorPorNombre(string $nombre): ?SectorConsultado
    {
        $sector = Sector::query()->where('nombre_clave', NombreClave::de($nombre))->orderByDesc('activo')->first();

        return $sector === null ? null : new SectorConsultado($sector->id, $sector->nombre, $sector->activo);
    }

    public function sectoresActivos(): array
    {
        return array_values(Sector::query()->where('activo', true)->orderBy('nombre')->get()
            ->map(static fn (Sector $s): SectorConsultado => new SectorConsultado($s->id, $s->nombre, true))->all());
    }

    public function puede(int $userId, string $funcion, string $nivel): bool
    {
        $user = User::query()->find($userId);

        return $user instanceof User && $this->permisos->puede($user, $funcion, Nivel::from($nivel));
    }

    public function sectorPuede(int $sectorId, string $funcion, string $nivel): bool
    {
        $celda = Permiso::query()
            ->where('sector_id', $sectorId)
            ->whereHas('funcion', static fn (Builder $q): Builder => $q->where('clave', $funcion))
            ->whereHas('sector', static fn (Builder $q): Builder => $q->where('activo', true))
            ->first();

        return $celda instanceof Permiso && $celda->nivel->alcanza(Nivel::from($nivel));
    }

    private static function usuario(User $user): UsuarioConsultado
    {
        return new UsuarioConsultado($user->id, $user->nombre, $user->email, $user->activo, $user->sector_id, $user->sector->nombre);
    }
}
