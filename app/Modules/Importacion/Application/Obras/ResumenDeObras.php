<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Application\RubrosDelSistema;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Resultado;

/**
 * Las filas por obra de la hoja Resumen y la hoja Control por rubro (§5.8): lo que administración compara con su
 * "General" (RN-IMP-24), con de qué filas sale cada número ("INSUMO 80.000 + INSUMOS 45.500,50").
 */
final readonly class ResumenDeObras
{
    public function __construct(private RubrosDelSistema $rubros) {}

    public function agregar(Informe $informe, ObraDelLote $obra, Resultado $resultado): void
    {
        $gastado = $obra->gastadoTotal();
        $planilla = $obra->totalPlanilla;
        $informe->resumenObras[] = [
            'numero' => $obra->numero,
            'trabajo' => $obra->trabajo,
            'estado' => $obra->estado,
            'entra' => match ($resultado) {
                Resultado::Importada => 'Sí',
                Resultado::YaEstaba => 'Ya estaba',
                default => 'No: '.count($obra->faltantes).(count($obra->faltantes) === 1 ? ' faltante' : ' faltantes'),
            },
            'presupuestado' => T::importe($obra->presupuestadoTotal()),
            'gastado' => T::importe($gastado),
            'planilla' => $planilla === null ? '' : T::importe($planilla),
            'diferencia' => $planilla === null ? '' : T::importe(bcsub($planilla, $gastado, 2)),
            'materiales' => count($obra->materiales),
            'con_retirado' => $obra->conRetirado(),
            'corte' => $obra->corte === null ? '' : T::fecha($obra->corte),
        ];

        foreach ($obra->gastado as $clave => $importe) {
            $informe->control[] = [
                'obra' => $obra->numero,
                'rubro' => $this->rubros->nombreDe($clave),
                'importe' => T::importe($importe),
                'de' => implode(' + ', $obra->origenDelGastado[$clave] ?? []),
            ];
        }
    }
}
