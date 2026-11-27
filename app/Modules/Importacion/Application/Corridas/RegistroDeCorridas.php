<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Importacion\Domain\EstadoCorrida;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Modo;
use App\Modules\Importacion\Domain\Resultado;
use App\Modules\Importacion\Infrastructure\Models\Corrida;
use App\Modules\Importacion\Infrastructure\Models\FilaImportada;
use Illuminate\Support\Carbon;

/**
 * Las corridas y sus filas (§11.3): huella, quién aprobó, estado y conteos; nunca valores de celdas (RN-IMP-13).
 * Es la memoria de la carga: el ensayo previo del mismo archivo (RN-IMP-04) y las Claves ya importadas (RN-IMP-08).
 */
final class RegistroDeCorridas
{
    public function empezar(Lote $lote, Modo $modo, string $huella, ?int $aproboId): Corrida
    {
        return Corrida::query()->create([
            'lote' => $lote, 'modo' => $modo, 'huella' => $huella, 'aprobo_id' => $aproboId, 'estado' => EstadoCorrida::EnCurso,
        ]);
    }

    /**
     * @param  array<string, int>  $resumen
     */
    public function terminar(Corrida $corrida, EstadoCorrida $estado, array $resumen, string $informe): void
    {
        $corrida->fill(['estado' => $estado, 'resumen' => $resumen, 'informe' => $informe, 'terminada_at' => Carbon::now()])->save();
    }

    /** RN-IMP-07: una corrida que quedó "en curso" sin bloqueo es una que se cortó. */
    public function marcarInterrumpidas(): void
    {
        Corrida::query()->where('estado', EstadoCorrida::EnCurso)->update(['estado' => EstadoCorrida::Interrumpida]);
    }

    /** RN-IMP-04, 05: un ensayo terminado de ese mismo archivo (misma huella) y ese lote. */
    public function hayEnsayo(Lote $lote, string $huella): bool
    {
        return Corrida::query()
            ->where('lote', $lote)
            ->where('huella', $huella)
            ->where('modo', Modo::Ensayo)
            ->whereIn('estado', [EstadoCorrida::Terminada, EstadoCorrida::ConErrores])
            ->exists();
    }

    /**
     * @param  list<FilaDeCorrida>  $filas
     */
    public function guardarFilas(int $corridaId, array $filas): void
    {
        foreach ($filas as $fila) {
            FilaImportada::query()->create([
                'corrida_id' => $corridaId,
                'hoja' => $fila->hoja,
                'clave' => mb_substr($fila->clave, 0, 150),
                'resultado' => $fila->resultado,
                'sujeto_type' => $fila->sujetoType,
                'sujeto_id' => $fila->sujetoId,
            ]);
        }
    }

    /**
     * RN-IMP-08: las Claves de la hoja que ya entraron en una carga confirmada, con lo que se creó.
     *
     * @param  list<string>  $claves
     * @return array<string, int>
     */
    public function importadas(string $hoja, array $claves): array
    {
        if ($claves === []) {
            return [];
        }

        $confirmadas = Corrida::query()->select('id')->where('modo', Modo::Confirmada);

        return array_map(intval(...), FilaImportada::query()
            ->where('hoja', $hoja)
            ->whereIn('clave', $claves)
            ->where('resultado', Resultado::Importada)
            ->whereIn('corrida_id', $confirmadas)
            ->pluck('sujeto_id', 'clave')
            ->all());
    }

    /**
     * Para `importacion:corridas`, la más nueva primero.
     *
     * @return list<Corrida>
     */
    public function todas(): array
    {
        return array_values(Corrida::query()->with('aprobo')->orderByDesc('id')->get()->all());
    }
}
