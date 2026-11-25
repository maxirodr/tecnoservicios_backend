<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Application\Carga\AlcanceDeCarga;
use App\Modules\Gastos\Application\Carga\CargaRepetida;
use App\Modules\Gastos\Application\Carga\DatosGasto;
use App\Modules\Gastos\Application\Carga\Errores;
use App\Modules\Gastos\Application\Carga\FacturaRepetida;
use App\Modules\Gastos\Application\Carga\ValidarGasto;
use App\Modules\Gastos\Domain\Events\GastoCorregido;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\AdjuntosService;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * F-46 / HU-072: corregir un gasto no anulado con `gastos = editar`, también lo cargado desde W-46 y de
 * una obra Terminada (RN-GAS-37, resuelta DU-R05). Mismas validaciones que la carga; tipo y origen no se
 * cambian. Con el gasto bloqueado y la versión verificada (409, RN-GAS-39). Guardar sin cambios no registra
 * nada; con cambios, `GastoCorregido` con las obras de antes ∪ las de después (RN-GAS-38).
 */
final readonly class CorregirGastoAction
{
    public const RENGLON_AJENO = 'Ese renglón no es de este comprobante.';

    public function __construct(
        private Connection $db,
        private BuscarGasto $buscar,
        private AccesoGasto $acceso,
        private DatosDeLaCorreccion $armar,
        private ValidarGasto $validar,
        private CargaRepetida $repetida,
        private FacturaRepetida $facturas,
        private EscribirCorreccion $escribir,
        private AdjuntosService $adjuntos,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws ValidationException|HttpResponseException 403 · 404 · 409 · 422
     */
    public function handle(User $actor, int $id, CambiosGasto $cambios, ?CarbonInterface $version = null): Gasto
    {
        return $this->db->transaction(function () use ($actor, $id, $cambios, $version): Gasto {
            $gasto = $this->buscar->paraCorregir($actor, $id);
            VersionDeFila::bloquearYVerificar($gasto, $version);

            $datos = $this->armar->armar($gasto, $cambios);
            $this->exigirAlcance($actor, $datos);
            $this->exigirRenglonesPropios($gasto, $datos);

            $nuevo = $this->validar->handle($datos);
            $this->repetida->verificar($actor, $nuevo, $datos, $gasto->id);

            $obrasAntes = $gasto->obraIds();
            $hubo = $this->escribir->aplicar($gasto, $nuevo, $actor->id);

            if ($cambios->adjuntos !== null) {
                $hubo = $this->cambiarAdjuntos($actor, $gasto, $cambios->adjuntos) || $hubo;
            }

            if (! $hubo) {
                return $gasto;
            }

            $gasto->updated_by = $actor->id;
            $this->guardar($actor, $gasto);
            $this->eventos->dispatch(new GastoCorregido($gasto->id, array_values(array_unique([...$obrasAntes, ...$nuevo->obraIds()]))));

            return $gasto;
        });
    }

    /** RN-GAS-49: con "solo sus obras", todos los renglones a sus obras (nunca "sin obra"). */
    private function exigirAlcance(User $actor, DatosGasto $datos): void
    {
        $visibles = $this->acceso->obrasVisibles($actor);

        if ($visibles === null) {
            return;
        }

        foreach ($datos->renglones as $renglon) {
            BuscarGasto::exigir($renglon->obraId !== null && in_array($renglon->obraId, $visibles, true), AlcanceDeCarga::SOLO_SUS_OBRAS);
        }
    }

    private function exigirRenglonesPropios(Gasto $gasto, DatosGasto $datos): void
    {
        $propios = $gasto->renglones->modelKeys();
        $errores = new Errores;

        foreach ($datos->renglones as $renglon) {
            if ($renglon->id !== null && ! in_array($renglon->id, $propios, true)) {
                $errores->renglon($renglon->posicion, 'id', self::RENGLON_AJENO);
            }
        }

        $errores->lanzarSiHay();
    }

    /**
     * @param  list<int>  $nuevos
     */
    private function cambiarAdjuntos(User $actor, Gasto $gasto, array $nuevos): bool
    {
        $actuales = array_map('intval', $gasto->adjuntos()->pluck('id')->all());
        $agregar = array_values(array_diff($nuevos, $actuales));
        $quitar = array_values(array_diff($actuales, $nuevos));
        $contexto = $gasto->contextoAdjuntos();

        if ($contexto !== null) {
            $this->adjuntos->asociar($actor->id, $agregar, $gasto, $contexto);
        }

        $this->adjuntos->quitar($quitar, $gasto);

        return $agregar !== [] || $quitar !== [];
    }

    private function guardar(User $actor, Gasto $gasto): void
    {
        try {
            // touch() guarda también la cabecera: updated_at cambia aunque solo cambien renglones o adjuntos (versión, 409).
            $gasto->touch();
        } catch (UniqueConstraintViolationException $e) {
            $this->facturas->exigirQueNoExista($actor, $gasto->numero, $gasto->proveedor_id, $gasto->id);

            throw $e;
        }
    }
}
