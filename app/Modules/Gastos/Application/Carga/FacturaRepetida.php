<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Http\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * RN-GAS-08, con alcance (gastos.md §8.2): la misma factura del mismo proveedor no se carga dos veces. A
 * quien ve esa factura se le dice cuál es y quién la cargó, con link a W-34; a quien no la ve (alcance
 * "solo sus obras"), solo que ya está cargada, sin id, fecha ni quién (IDOR).
 */
final readonly class FacturaRepetida
{
    private const INVALIDO = 422;

    public function __construct(private AccesoGasto $acceso) {}

    public function existente(int $proveedorId, string $numero, ?int $excluir = null): ?Gasto
    {
        return Gasto::query()->with(['cargadoPor', 'proveedor', 'renglones'])
            ->where('proveedor_id', $proveedorId)
            ->where('numero_vigente', $numero)
            ->when($excluir !== null, static fn (Builder $q) => $q->whereKeyNot($excluir))
            ->first();
    }

    /**
     * `GET /api/gastos/factura-existente`: el aviso al salir del campo Número.
     *
     * @return array<string, mixed>
     */
    public function aviso(User $user, int $proveedorId, string $numero, ?int $excluir): array
    {
        $existente = $this->existente($proveedorId, $numero, $excluir);

        if ($existente === null) {
            return ['existe' => false];
        }

        if (! $this->acceso->veGasto($user, $existente)) {
            return ['existe' => true, 'mensaje' => self::sinDetalle($existente)];
        }

        return ['existe' => true, 'gasto' => self::resumen($existente), 'mensaje' => self::mensaje($existente)];
    }

    /**
     * @throws HttpResponseException 422 si ya está cargada
     */
    public function exigirQueNoExista(User $user, ?string $numero, int $proveedorId, ?int $excluir = null): void
    {
        $existente = $numero === null ? null : $this->existente($proveedorId, $numero, $excluir);

        if ($existente === null) {
            return;
        }

        if (! $this->acceso->veGasto($user, $existente)) {
            $mensaje = self::sinDetalle($existente);

            throw new HttpResponseException(ApiResponse::error($mensaje, self::INVALIDO, ['numero' => [$mensaje]]));
        }

        throw Errores::yaCargado('numero', self::mensaje($existente), self::resumen($existente));
    }

    /** "Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 17-oct)." (quién y cuándo la cargó). */
    private static function mensaje(Gasto $existente): string
    {
        $cuando = EtiquetaDocumento::diaYMes(FechaEmpresa::diaDe($existente->created_at));

        return "Esa factura ya está cargada: {$existente->numero} de {$existente->proveedor->nombre} ({$existente->cargadoPor->nombre}, {$cuando}).";
    }

    /** Lo que ya escribió quien carga (número y proveedor), sin datos de la factura que no ve. */
    private static function sinDetalle(Gasto $existente): string
    {
        return "Esa factura ya está cargada: {$existente->numero} de {$existente->proveedor->nombre}.";
    }

    /**
     * @return array{id: int, numero: string|null, fecha: string, cargado_por: array{id: int, nombre: string}}
     */
    private static function resumen(Gasto $existente): array
    {
        return [
            'id' => $existente->id,
            'numero' => $existente->numero,
            'fecha' => $existente->fecha->toDateString(),
            'cargado_por' => ['id' => $existente->cargadoPor->id, 'nombre' => $existente->cargadoPor->nombre],
        ];
    }
}
