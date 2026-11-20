<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Recorrido;

use App\Modules\Avisos\Contracts\AvisoEnviado;
use App\Modules\Avisos\Contracts\AvisosDelPedido;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Application\Pedidos\ActualizarPedidoAction;
use App\Modules\Pedidos\Application\Pedidos\CrearPedidoAction;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\Actividad;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * `GET /api/pedidos/{id}/recorrido` (F-34, RN-PED-31): del más viejo al más nuevo, cada acción con
 * cuándo, quién y qué, más los avisos que mandó el sistema (`AvisosDelPedido`, de Avisos).
 * `solo=cambios` es el "Historial de cambios" de W-32 (alta, ediciones y adjuntos), el más nuevo arriba.
 */
final readonly class RecorridoQuery
{
    private const VENCE_MANANA = 'vence_manana';

    /** Qué se le avisó, con palabras de la empresa y no la clave del tipo (avisos.md §6, PU-607). */
    private const QUE_AVISO = [
        'asignado' => 'asignado',
        'reasignado' => 'reasignado',
        'tomado' => 'tomado',
        'bloqueado' => 'bloqueado',
        'comentado' => 'comentario nuevo',
        'completado' => 'completado',
        'completado_gente' => 'completado, a su jefe',
        'reabierto' => 'reabierto',
    ];

    public function __construct(
        private BuscarPedido $buscar,
        private AvisosDelPedido $avisos,
    ) {}

    /**
     * @return list<array{cuando: string|null, quien: array{id: int, nombre: string}|null, que: string}>
     *
     * @throws HttpResponseException 404 fuera de alcance
     */
    public function handle(User $user, int $id, bool $soloCambios): array
    {
        $pedido = $this->buscar->visible($user, $id);
        $actividades = Actividad::query()
            ->with('user')
            ->where('sujeto_type', Pedido::MORPH)
            ->where('sujeto_id', $pedido->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($soloCambios) {
            return array_reverse($this->cambios(array_values($actividades->all())));
        }

        $lineas = [];

        foreach ($actividades as $actividad) {
            foreach (TextoDeActividad::lineas($actividad) as $que) {
                $lineas[] = ['orden' => $actividad->created_at->getTimestamp(), ...self::linea($actividad, $que)];
            }
        }

        foreach ($this->avisos->enviadosDe($pedido->id) as $aviso) {
            $lineas[] = ['orden' => $aviso->createdAt->getTimestamp(), 'cuando' => FechaEmpresa::instante($aviso->createdAt), 'quien' => null, 'que' => self::aviso($aviso)];
        }

        // Orden estable: a igual segundo, la acción va antes que el aviso que generó.
        usort($lineas, static fn (array $a, array $b): int => $a['orden'] <=> $b['orden']);

        return array_map(static fn (array $l): array => ['cuando' => $l['cuando'], 'quien' => $l['quien'], 'que' => $l['que']], $lineas);
    }

    /**
     * @param  list<Actividad>  $actividades
     * @return list<array{cuando: string|null, quien: array{id: int, nombre: string}|null, que: string}>
     */
    private function cambios(array $actividades): array
    {
        $lineas = [];

        foreach ($actividades as $actividad) {
            $textos = match (true) {
                $actividad->accion === CrearPedidoAction::ACCION => ['Creó el pedido'],
                $actividad->accion === ActualizarPedidoAction::ACCION => TextoDeCambios::edicion($actividad->cambios ?? []),
                TextoDeCambios::esAdjunto($actividad->accion) => TextoDeCambios::adjuntos(
                    $actividad->cambios ?? [],
                    str_ends_with($actividad->accion, 'agregado') ? 'agregado' : 'quitado',
                ),
                default => [],
            };

            foreach ($textos as $que) {
                $lineas[] = self::linea($actividad, $que);
            }
        }

        return $lineas;
    }

    /**
     * @return array{cuando: string|null, quien: array{id: int, nombre: string}|null, que: string}
     */
    private static function linea(Actividad $actividad, string $que): array
    {
        return [
            'cuando' => FechaEmpresa::instante($actividad->created_at),
            'quien' => $actividad->user === null ? null : ['id' => $actividad->user->id, 'nombre' => $actividad->user->nombre],
            'que' => $que,
        ];
    }

    private static function aviso(AvisoEnviado $aviso): string
    {
        if ($aviso->tipo === self::VENCE_MANANA) {
            return 'Sistema: recordatorio, vence mañana';
        }

        $nombres = implode(', ', array_map(static fn (array $d): string => $d['nombre'], $aviso->destinatarios));

        return "Sistema avisó a {$nombres} (".(self::QUE_AVISO[$aviso->tipo] ?? 'aviso').')';
    }
}
