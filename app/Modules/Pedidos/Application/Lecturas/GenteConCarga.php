<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Acceso\MiGente;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Las personas de las vistas de jefatura (W-26, W-04): mi gente (directa o indirecta; Dirección, todos)
 * **y yo**, con abiertas, vencidas y "vencen esta semana" (de hoy al domingo, sin las vencidas), en la
 * hora de la empresa (resuelta DU-P14). Las inactivas aparecen solo si todavía tienen abiertas (DU-P09).
 * Dos consultas para toda la gente: las personas y los números agrupados por propietario.
 */
final readonly class GenteConCarga
{
    public const SIN_GENTE = 'Esta sección es para quien tiene gente a cargo.';

    public function __construct(private MiGente $gente) {}

    /**
     * @return list<CargaDePersona>
     *
     * @throws HttpResponseException 403 sin gente a cargo (RN-PED-35)
     */
    public function de(User $user): array
    {
        BuscarPedido::exigir($this->gente->tieneGenteActiva($user), self::SIN_GENTE);
        $gente = $this->gente->ids($user);
        $ids = $gente === null ? null : [$user->id, ...$gente];
        $personas = User::query()->with('sector')
            ->when($ids !== null, static fn ($q) => $q->whereKey($ids))
            ->orderBy('nombre')
            ->get();
        $numeros = $this->numeros($ids);
        $cargas = [];

        foreach ($personas as $persona) {
            $suyos = $numeros[$persona->id] ?? ['abiertas' => 0, 'vencidas' => 0, 'semana' => 0];

            if ($persona->activo || $suyos['abiertas'] > 0) {
                $cargas[] = new CargaDePersona($persona, $persona->id === $user->id, $suyos['abiertas'], $suyos['vencidas'], $suyos['semana']);
            }
        }

        return $cargas;
    }

    /**
     * @param  list<int>|null  $ids  null = todos (Dirección)
     * @return array<int, array{abiertas: int, vencidas: int, semana: int}>
     */
    private function numeros(?array $ids): array
    {
        $hoy = FechaEmpresa::hoyTexto();
        $semana = FechaEmpresa::semana();
        $filas = Pedido::query()->toBase()
            ->whereIn('estado', PedidoEstado::abiertos())
            ->whereNotNull('propietario_id')
            ->when($ids !== null, static fn ($q) => $q->whereIn('propietario_id', $ids))
            ->groupBy('propietario_id')
            ->selectRaw(
                'propietario_id, COUNT(*) AS abiertas, SUM(fecha_entrega < ?) AS vencidas, SUM(fecha_entrega BETWEEN ? AND ?) AS semana',
                [$hoy, $hoy, $semana['hasta']],
            )
            ->get();
        $numeros = [];

        foreach ($filas as $fila) {
            $numeros[(int) $fila->propietario_id] = [
                'abiertas' => (int) $fila->abiertas,
                'vencidas' => (int) $fila->vencidas,
                'semana' => (int) $fila->semana,
            ];
        }

        return $numeros;
    }
}
