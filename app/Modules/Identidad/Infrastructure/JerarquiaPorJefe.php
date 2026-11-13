<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Contracts\Jerarquia;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Jerarquía sobre `users.jefe_id` (RN-IDE-23, ADR-006). "Mi gente" con CTE recursivo; el UNION
 * (sin ALL) corta cualquier ciclo que se hubiera colado en los datos.
 */
final class JerarquiaPorJefe implements Jerarquia
{
    private const GENTE_SQL = <<<'SQL'
        WITH RECURSIVE gente (id) AS (
            SELECT id FROM users WHERE jefe_id = ?
            UNION
            SELECT u.id FROM users u INNER JOIN gente g ON u.jefe_id = g.id
        )
        SELECT id FROM gente ORDER BY id
        SQL;

    /**
     * @return list<int>
     */
    public function subordinados(User $user): array
    {
        return array_values(collect(DB::select(self::GENTE_SQL, [$user->id]))
            ->pluck('id')
            ->map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)
            ->all());
    }

    public function superior(User $user): ?User
    {
        if ($user->jefe_id === null) {
            return null;
        }

        return User::query()->find($user->jefe_id);
    }
}
