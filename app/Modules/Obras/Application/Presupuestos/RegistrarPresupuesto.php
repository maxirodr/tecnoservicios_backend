<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use Illuminate\Validation\ValidationException;

/**
 * Crea un presupuesto de la obra con su oferta técnica y su documento de aprobación (archivo o
 * link). Corre dentro de la transacción del caso de uso que lo llama (alta de obra o adicional).
 * La unicidad del número la verifica antes quien llama (`NumeroUnico`).
 */
final readonly class RegistrarPresupuesto
{
    public function __construct(private AdjuntosDeObra $adjuntos) {}

    /**
     * @param  string  $campo  prefijo del campo para los 422 (`presupuesto`, `adicionales.0`, ``)
     *
     * @throws ValidationException
     */
    public function handle(Obra $obra, DatosPresupuesto $datos, bool $esPrincipal, int $actorId, string $campo): Presupuesto
    {
        $presupuesto = Presupuesto::query()->create([
            'obra_id' => $obra->id,
            'numero' => $datos->numero,
            'revision' => $datos->revision,
            'es_principal' => $esPrincipal,
            'descripcion' => $datos->descripcion,
            'aprobacion_tipo' => $datos->aprobacion?->tipo,
            'aprobacion_numero' => $datos->aprobacion?->numero,
            'created_by' => $actorId,
        ]);

        if ($datos->oferta !== null) {
            $this->adjuntos->asociar([$datos->oferta], $presupuesto, Presupuesto::CONTEXTO_OFERTA, self::campo($campo, 'oferta'));
        }

        if ($datos->aprobacion?->adjunto !== null) {
            $this->adjuntos->asociar([$datos->aprobacion->adjunto], $presupuesto, Presupuesto::CONTEXTO_APROBACION, self::campo($campo, 'aprobacion.adjunto'));
        }

        return $presupuesto;
    }

    public static function campo(string $prefijo, string $campo): string
    {
        return $prefijo === '' ? $campo : "{$prefijo}.{$campo}";
    }
}
