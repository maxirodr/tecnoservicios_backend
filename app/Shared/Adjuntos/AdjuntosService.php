<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

use App\Shared\Actividad\RegistroActividad;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Lo que usan los casos de uso de los módulos dueños, dentro de su transacción (ADR-014):
 * asociar adjuntos temporales al recurso y quitarlos. No autoriza: el caso de uso ya verificó
 * que el usuario puede editar el recurso. Cada alta y baja deja actividad en el sujeto (ADR-010).
 */
final readonly class AdjuntosService
{
    public const MENSAJE_NO_UTILIZABLE = 'Ese adjunto no se puede usar acá.';

    public const CAMPO_ERROR = 'adjuntos';

    public const ACCION_AGREGADO = 'adjunto_agregado';

    public const ACCION_QUITADO = 'adjunto_quitado';

    public function __construct(
        private Connection $db,
        private AuthFactory $auth,
        private ContextosAdjuntos $contextos,
        private RegistroActividad $actividad,
        private FilesystemFactory $filesystem,
    ) {}

    /**
     * Solo se asocian adjuntos temporales, subidos por quien llama y del contexto del campo (RN-IDE-46);
     * si alguno no cumple, no se asocia ninguno: 422 en `adjuntos`.
     *
     * @param  array<array-key, int|string>  $ids
     *
     * @throws ValidationException
     */
    public function asociar(array $ids, Model $sujeto, string $contexto): void
    {
        $ids = self::normalizarIds($ids);

        if ($ids === []) {
            return;
        }

        $userId = $this->auth->guard()->id();

        if (! is_numeric($userId) || ! $this->contextos->existe($contexto)) {
            throw self::noUtilizable();
        }

        $this->db->transaction(function () use ($ids, $sujeto, $contexto, $userId): void {
            $adjuntos = $this->bloquear($ids);
            $validos = $adjuntos->filter(fn (Adjunto $a): bool => $a->esTemporal()
                && $a->subido_por === (int) $userId
                && $a->contexto === $contexto);

            if ($validos->count() !== count($ids)) {
                throw self::noUtilizable();
            }

            Adjunto::query()->whereKey($ids)->update([
                'sujeto_type' => $sujeto->getMorphClass(),
                'sujeto_id' => $sujeto->getKey(),
            ]);

            foreach ($adjuntos as $adjunto) {
                $this->actividad->registrar($sujeto, self::ACCION_AGREGADO, [
                    $contexto => ['antes' => null, 'despues' => $adjunto->nombre],
                ]);
            }
        });
    }

    /**
     * Quita adjuntos del recurso: borra la fila, deja actividad con el nombre (sin URL ni ruta) y,
     * cuando la transacción confirma, borra el archivo físico (RN-IDE-48).
     *
     * @param  array<array-key, int|string>  $ids
     *
     * @throws ValidationException
     */
    public function quitar(array $ids, Model $sujeto): void
    {
        $ids = self::normalizarIds($ids);

        if ($ids === []) {
            return;
        }

        $this->db->transaction(function () use ($ids, $sujeto): void {
            $adjuntos = $this->bloquear($ids);

            if ($adjuntos->count() !== count($ids) || $adjuntos->contains(fn (Adjunto $a): bool => ! $a->perteneceA($sujeto))) {
                throw self::noUtilizable();
            }

            Adjunto::query()->whereKey($ids)->delete();

            foreach ($adjuntos as $adjunto) {
                $this->actividad->registrar($sujeto, self::ACCION_QUITADO, [
                    $adjunto->contexto => ['antes' => $adjunto->nombre, 'despues' => null],
                ]);
            }

            $this->db->afterCommit(fn () => $this->borrarArchivos($adjuntos));
        });
    }

    /**
     * @param  Collection<int, Adjunto>  $adjuntos
     */
    public function borrarArchivos(Collection $adjuntos): void
    {
        foreach ($adjuntos as $adjunto) {
            if ($adjunto->esArchivo() && $adjunto->disco !== null && $adjunto->ruta !== null) {
                $this->filesystem->disk($adjunto->disco)->delete($adjunto->ruta);
            }
        }
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Adjunto>
     */
    private function bloquear(array $ids): Collection
    {
        return Adjunto::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  array<array-key, int|string>  $ids
     * @return list<int>
     */
    private static function normalizarIds(array $ids): array
    {
        $normalizados = [];

        foreach ($ids as $id) {
            if (! is_int($id) && ! ctype_digit($id)) {
                throw self::noUtilizable();
            }

            $normalizados[(int) $id] = (int) $id;
        }

        return array_values($normalizados);
    }

    private static function noUtilizable(): ValidationException
    {
        return ValidationException::withMessages([self::CAMPO_ERROR => [self::MENSAJE_NO_UTILIZABLE]]);
    }
}
