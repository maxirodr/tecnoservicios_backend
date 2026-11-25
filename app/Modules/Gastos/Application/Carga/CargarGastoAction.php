<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * F-39…F-47 / HU-061…HU-070: cargar una factura o un gasto, desde cualquier pantalla (W-10, W-35, W-46).
 * Cabecera, renglones, adjuntos y la actividad `creado` en una sola transacción; `GastoImputado` con las
 * obras afectadas sale después del commit (RN-GAS-16, 26). Dos cargas de la misma factura a la vez: el
 * único `numero_vigente` deja una y la otra recibe el 422 (RN-GAS-08).
 */
final readonly class CargarGastoAction
{
    public const ACCION = 'creado';

    public function __construct(
        private Connection $db,
        private AlcanceDeCarga $alcance,
        private ValidarGasto $validar,
        private CargaRepetida $repetida,
        private FacturaRepetida $facturas,
        private GuardarRenglones $renglones,
        private AdjuntosService $adjuntos,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws ValidationException|HttpResponseException 403 · 422
     */
    public function handle(User $actor, DatosGasto $datos): Gasto
    {
        $this->alcance->exigir($actor, $datos->origen, array_map(static fn (DatosRenglon $r): ?int => $r->obraId, $datos->renglones));
        $aGuardar = $this->validar->handle($datos);

        return $this->db->transaction(function () use ($actor, $datos, $aGuardar): Gasto {
            $this->repetida->verificar($actor, $aGuardar, $datos);
            $gasto = $this->crearCabecera($actor, $aGuardar);
            $this->renglones->crear($gasto, $aGuardar->renglones, $actor->id);

            $this->actividad->registrar($gasto, self::ACCION, [
                'renglones' => ['antes' => null, 'despues' => count($aGuardar->renglones)],
            ]);

            $contexto = $gasto->contextoAdjuntos();

            if ($contexto !== null) {
                $this->adjuntos->asociar($actor->id, $datos->adjuntos, $gasto, $contexto);
            }

            $this->eventos->dispatch(new GastoImputado($gasto->id, $aGuardar->obraIds()));

            return $gasto;
        });
    }

    private function crearCabecera(User $actor, GastoAGuardar $gasto): Gasto
    {
        try {
            return Gasto::query()->create([
                'tipo_documento' => $gasto->origen->tipoDocumento(),
                'origen' => $gasto->origen,
                'numero' => $gasto->numero,
                'fecha' => $gasto->fecha,
                'proveedor_id' => $gasto->proveedorId,
                'rendido_por' => $gasto->rendidoPor,
                'periodo_desde' => $gasto->periodoDesde,
                'periodo_hasta' => $gasto->periodoHasta,
                'cargado_por' => $actor->id,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $this->facturas->exigirQueNoExista($actor, $gasto->numero, $gasto->proveedorId);

            throw $e;
        }
    }
}
