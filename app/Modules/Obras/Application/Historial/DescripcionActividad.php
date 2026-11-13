<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Historial;

use App\Modules\Obras\Application\Documentos\AgregarDocumentoAction;
use App\Modules\Obras\Application\Documentos\QuitarDocumentoAction;
use App\Modules\Obras\Application\Estado\CambiarEstadoObraAction;
use App\Modules\Obras\Application\Obras\CrearObraAction;
use App\Modules\Obras\Application\Presupuestos\AgregarPresupuestoAction;
use App\Modules\Obras\Application\Presupuestos\QuitarPresupuestoAction;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Enums\TipoDocumento;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\AdjuntosService;

/**
 * "Qué cambió" en palabras del cliente (RN-OBR-27): "N.º de OC: (vacío) → 4500-000123",
 * "Oferta técnica: agregado (oferta.pdf)", "Cambió el estado: En ejecución → Terminada". Los
 * adjuntos se informan con su nombre, nunca con la URL ni la ruta.
 */
final readonly class DescripcionActividad
{
    public function __construct(private EtiquetasHistorial $etiquetas) {}

    /**
     * @param  array<int, Presupuesto>  $presupuestos
     * @param  array<int, ObraDocumento>  $documentos
     */
    public function entrada(Actividad $actividad, array $presupuestos, array $documentos): EntradaHistorial
    {
        return new EntradaHistorial(
            $actividad->id,
            $actividad->created_at,
            $actividad->user?->id,
            $actividad->user?->nombre,
            $this->lineas($actividad, self::prefijo($actividad, $presupuestos, $documentos)),
        );
    }

    /**
     * @return list<string>
     */
    private function lineas(Actividad $actividad, string $prefijo): array
    {
        $cambios = $actividad->cambios ?? [];
        $primero = $cambios === [] ? null : $cambios[array_key_first($cambios)];

        return match ($actividad->accion) {
            CrearObraAction::ACCION => ['Creó la obra'],
            CambiarEstadoObraAction::ACCION => ['Cambió el estado: '.self::estado($primero['antes'] ?? null).' → '.self::estado($primero['despues'] ?? null)],
            AgregarPresupuestoAction::ACCION => ['Agregó el presupuesto adicional '.self::texto($primero['despues'] ?? null)],
            QuitarPresupuestoAction::ACCION => ['Quitó el presupuesto adicional '.self::texto($primero['antes'] ?? null)],
            AgregarDocumentoAction::ACCION => ['Agregó '.self::documento($cambios)." '".self::texto($primero['despues'] ?? null)."'"],
            QuitarDocumentoAction::ACCION => ['Quitó '.self::documento($cambios).' '.self::texto($primero['antes'] ?? null)],
            AdjuntosService::ACCION_AGREGADO => $this->adjuntos($cambios, $prefijo, 'agregado', 'despues'),
            AdjuntosService::ACCION_QUITADO => $this->adjuntos($cambios, $prefijo, 'quitado', 'antes'),
            default => $this->campos($cambios, $prefijo),
        };
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     * @return list<string>
     */
    private function campos(array $cambios, string $prefijo): array
    {
        $lineas = [];

        foreach ($cambios as $campo => $cambio) {
            $etiqueta = $this->etiquetas->campo($campo);
            $lineas[] = $this->etiquetas->esLink($campo)
                ? "{$prefijo}{$etiqueta}: ".self::estadoDeLink($cambio)
                : "{$prefijo}{$etiqueta}: ".$this->etiquetas->valor($campo, $cambio['antes']).' → '.$this->etiquetas->valor($campo, $cambio['despues']);
        }

        return $lineas;
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     * @return list<string>
     */
    private function adjuntos(array $cambios, string $prefijo, string $verbo, string $lado): array
    {
        $lineas = [];

        foreach ($cambios as $contexto => $cambio) {
            $lineas[] = "{$prefijo}{$this->etiquetas->contexto($contexto)}: {$verbo} (".self::texto($cambio[$lado]).')';
        }

        return $lineas;
    }

    /**
     * @param  array<int, Presupuesto>  $presupuestos
     * @param  array<int, ObraDocumento>  $documentos
     */
    private static function prefijo(Actividad $actividad, array $presupuestos, array $documentos): string
    {
        if ($actividad->sujeto_type === Presupuesto::MORPH) {
            $presupuesto = $presupuestos[$actividad->sujeto_id] ?? null;

            return $presupuesto === null || $presupuesto->es_principal ? '' : "Adicional {$presupuesto->numero} · ";
        }

        if ($actividad->sujeto_type === ObraDocumento::MORPH) {
            $documento = $documentos[$actividad->sujeto_id] ?? null;

            return $documento === null ? '' : "{$documento->nombre} · ";
        }

        return '';
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     */
    private static function documento(array $cambios): string
    {
        return TipoDocumento::tryFrom((string) array_key_first($cambios))?->etiqueta() ?? 'el documento';
    }

    /**
     * @param  array{antes: mixed, despues: mixed}  $cambio
     */
    private static function estadoDeLink(array $cambio): string
    {
        return match (true) {
            $cambio['antes'] === null => 'agregado',
            $cambio['despues'] === null => 'quitado',
            default => 'cambiado',
        };
    }

    private static function estado(mixed $valor): string
    {
        return ObraEstado::tryFrom(self::texto($valor))?->etiqueta() ?? self::texto($valor);
    }

    private static function texto(mixed $valor): string
    {
        return is_scalar($valor) ? (string) $valor : '';
    }
}
