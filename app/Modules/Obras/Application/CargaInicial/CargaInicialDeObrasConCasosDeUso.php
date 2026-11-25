<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\CargaInicial;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Documentos\AgregarDocumentoAction;
use App\Modules\Obras\Application\Documentos\DatosDocumento;
use App\Modules\Obras\Application\Estado\CambiarEstadoObraAction;
use App\Modules\Obras\Application\Montos\CargarMontoObraAction;
use App\Modules\Obras\Application\Montos\CargarMontosAction;
use App\Modules\Obras\Application\Obras\CrearObraAction;
use App\Modules\Obras\Application\Obras\DatosObra;
use App\Modules\Obras\Application\Presupuestos\DatosAprobacion;
use App\Modules\Obras\Application\Presupuestos\DatosPresupuesto;
use App\Modules\Obras\Contracts\CargaInicialDeObras;
use App\Modules\Obras\Contracts\DocumentoInicial;
use App\Modules\Obras\Contracts\ObraInicial;
use App\Modules\Obras\Contracts\PresupuestoInicial;
use App\Modules\Obras\Domain\Enums\AprobacionTipo;
use App\Modules\Obras\Domain\Enums\ModoCarga;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Enums\TipoDocumento;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Domain\Valores\NumeroPresupuesto;
use App\Modules\Obras\Domain\Valores\Revision;
use App\Modules\Obras\Domain\Valores\ValorInvalido;
use App\Shared\Adjuntos\Application\RegistrarLinkAction;
use App\Shared\CargaInicial\CargaRechazada;
use App\Shared\Domain\Importe;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;

/**
 * `CargaInicialDeObras` por los casos de uso de la pantalla (RN-IMP-09): alta de obra (W-06), cambio de estado
 * (W-22), montos (W-13), monto de la obra y documentos (W-20). Los links entran como adjuntos temporales de quien
 * aprobó y se asocian como en la pantalla (ADR-014). Todo en una transacción: si algo falla, no queda nada.
 */
final readonly class CargaInicialDeObrasConCasosDeUso implements CargaInicialDeObras
{
    public function __construct(
        private Connection $db,
        private CrearObraAction $crear,
        private CambiarEstadoObraAction $estado,
        private CargarMontosAction $montos,
        private CargarMontoObraAction $montoObra,
        private AgregarDocumentoAction $documentos,
        private RegistrarLinkAction $links,
    ) {}

    public function numero(string $escrito): string
    {
        try {
            return NumeroPresupuesto::desde($escrito, Carbon::now(config()->string('app.zona_empresa'))->year)->valor();
        } catch (ValorInvalido $e) {
            throw CargaRechazada::por($e->getMessage());
        }
    }

    public function revision(string $escrita): string
    {
        try {
            return Revision::desde($escrita)->valor;
        } catch (ValorInvalido $e) {
            throw CargaRechazada::por($e->getMessage());
        }
    }

    public function existe(string $numeroPrincipal): ?int
    {
        $obraId = Presupuesto::query()->where('numero', $numeroPrincipal)->where('es_principal', true)->value('obra_id');

        return is_numeric($obraId) ? (int) $obraId : null;
    }

    public function obraConNumero(string $numero): ?string
    {
        $presupuesto = Presupuesto::query()->with('obra:id,nombre_trabajo')->where('numero', $numero)->first();

        return $presupuesto?->obra->nombre_trabajo;
    }

    public function estado(int $obraId): ?string
    {
        return Obra::query()->find($obraId)?->estado->value;
    }

    public function crear(int $actorId, ObraInicial $obra): int
    {
        return CargaRechazada::traducir(fn (): int => $this->db->transaction(function () use ($actorId, $obra): int {
            $actor = User::query()->findOrFail($actorId);

            $creada = $this->crear->handle($actor, new DatosObra(
                $obra->nombreTrabajo,
                $obra->cliente,
                $obra->planta,
                $obra->responsableId,
                $obra->fechaInicioPrevista,
                $obra->fechaFinPrevista,
                $obra->carpetaDriveUrl,
                $this->presupuesto($actor, $obra->presupuestos[0]),
                array_map(fn (PresupuestoInicial $p): DatosPresupuesto => $this->presupuesto($actor, $p), array_slice($obra->presupuestos, 1)),
                $obra->contactos,
            ));

            if ($obra->enEjecucion) {
                $this->estado->handle($actor, $creada, ObraEstado::EnEjecucion, []);
            }

            $this->cargarMontos($creada, $obra->presupuestos);

            return $creada->id;
        }));
    }

    public function agregarDocumento(int $actorId, int $obraId, DocumentoInicial $documento): int
    {
        return CargaRechazada::traducir(fn (): int => $this->db->transaction(function () use ($actorId, $obraId, $documento): int {
            $actor = User::query()->findOrFail($actorId);
            $adjuntos = array_map(fn (string $url): int => $this->link($actor, ObraDocumento::CONTEXTO, $url), $documento->links);

            return $this->documentos->handle($actor, Obra::query()->findOrFail($obraId), new DatosDocumento(
                TipoDocumento::from($documento->tipo),
                $documento->nombre,
                $documento->fecha,
                $adjuntos,
            ))->id;
        }));
    }

    /**
     * Por rubro o solo el total (RN-OBR-31) y el monto del presupuesto (RN-OBR-36), por los casos de uso de W-13.
     *
     * @param  list<PresupuestoInicial>  $presupuestos
     */
    private function cargarMontos(Obra $obra, array $presupuestos): void
    {
        $porNumero = Presupuesto::query()->where('obra_id', $obra->id)->get()->keyBy('numero');

        foreach ($presupuestos as $inicial) {
            $presupuesto = $porNumero->get($inicial->numero);

            if (! $presupuesto instanceof Presupuesto) {
                continue;
            }

            if ($inicial->modo !== ModoCarga::SinCargar->value) {
                $montos = $inicial->modo === ModoCarga::SoloTotal->value
                    ? [CargarMontosAction::CLAVE_TOTAL => Importe::desde((string) $inicial->total)]
                    : array_map(static fn (string $monto): Importe => Importe::desde($monto), $inicial->montos);
                $this->montos->handle($presupuesto, ModoCarga::from($inicial->modo), $montos);
            }

            if ($inicial->montoObra !== null) {
                $this->montoObra->handle($presupuesto->refresh(), Importe::desde($inicial->montoObra));
            }
        }
    }

    private function presupuesto(User $actor, PresupuestoInicial $p): DatosPresupuesto
    {
        $aprobacion = $p->aprobacionTipo === null && $p->aprobacionNumero === null && $p->aprobacionUrl === null
            ? null
            : new DatosAprobacion(
                AprobacionTipo::from($p->aprobacionTipo ?? AprobacionTipo::Oc->value),
                $p->aprobacionNumero,
                $p->aprobacionUrl === null ? null : $this->link($actor, Presupuesto::CONTEXTO_APROBACION, $p->aprobacionUrl),
            );

        return new DatosPresupuesto(
            $p->numero,
            $p->revision,
            $p->ofertaUrl === null ? null : $this->link($actor, Presupuesto::CONTEXTO_OFERTA, $p->ofertaUrl),
            $p->descripcion,
            $aprobacion,
        );
    }

    private function link(User $actor, string $contexto, string $url): int
    {
        return $this->links->handle($actor, $contexto, $url, null)->id;
    }
}
