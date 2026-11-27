<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Tareas;

use App\Modules\Importacion\Application\Corridas\Contexto;
use App\Modules\Importacion\Application\Corridas\FilaDeCorrida;
use App\Modules\Importacion\Application\Corridas\ImportadorDeLote;
use App\Modules\Importacion\Application\Corridas\RegistroDeCorridas;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;
use App\Modules\Importacion\Domain\Resultado;
use App\Modules\Obras\Contracts\CargaInicialDeObras;
use App\Modules\Pedidos\Contracts\CargaInicialDeTareas;
use App\Shared\CargaInicial\CargaRechazada;
use Illuminate\Database\Connection;
use Throwable;

/**
 * El lote tareas (RN-IMP-02, 07, 08, 32…38): tareas abiertas y documentos de la minuta, **una transacción por fila**.
 * Una Clave repetida en el archivo es faltante en todas sus filas; una ya importada en una carga confirmada no se toca.
 */
final readonly class ImportarTareas implements ImportadorDeLote
{
    public function __construct(
        private Connection $db,
        private LeerTareas $leer,
        private RegistroDeCorridas $corridas,
        private CargaInicialDeTareas $tareas,
        private CargaInicialDeObras $obras,
    ) {}

    public function lote(): Lote
    {
        return Lote::Tareas;
    }

    public function correr(array $hojas, Contexto $ctx): array
    {
        $this->leer->personas($hojas[C::HOJA_PERSONAS] ?? []);
        $filas = $hojas[C::HOJA_TAREAS] ?? [];
        $repetidas = self::repetidas($filas);
        $importadas = $this->corridas->importadas(C::HOJA_TAREAS, array_map(strval(...), array_keys(self::porClave($filas))));
        $c = ['filas' => count($filas), 'tareas' => 0, 'documentos' => 0, 'completadas' => 0, 'ya_importadas' => 0, 'con_faltantes' => 0];

        foreach ($filas as $fila) {
            $clave = $fila->texto(C::CLAVE) ?? '';

            if ($clave !== '' && isset($importadas[$clave]) && ! isset($repetidas[$clave])) {
                $c['ya_importadas']++;
                $ctx->registrar([new FilaDeCorrida(C::HOJA_TAREAS, $clave, Resultado::YaEstaba, null, $importadas[$clave])]);

                continue;
            }

            $tarea = $this->leer->fila($fila, $ctx->informe);

            if (isset($repetidas[$clave])) {
                $tarea->faltantes[] = new Faltante(C::HOJA_TAREAS, $fila->numero, $clave, C::CLAVE, "La clave '{$clave}' está repetida en las filas ".self::enumerar($repetidas[$clave]).'.', Informe::ADMINISTRACION);
            }

            $resultado = $tarea->completada ? Resultado::NoSeImporta : ($tarea->entra() ? $this->guardar($tarea, $ctx) : Resultado::Faltante);
            $c[match (true) {
                $resultado === Resultado::NoSeImporta => 'completadas',
                $resultado === Resultado::Faltante => 'con_faltantes',
                $tarea->tipo === TareaDelLote::DOCUMENTO => 'documentos',
                default => 'tareas',
            }]++;
            $this->informar($ctx->informe, $tarea);

            if ($resultado !== Resultado::Importada) {
                $ctx->registrar([new FilaDeCorrida(C::HOJA_TAREAS, $tarea->clave, $resultado)]);
            }
        }

        $ctx->informe->resumenTareas = $c;

        return $c;
    }

    public function mensaje(array $conteos, Contexto $ctx, string $informe): string
    {
        $c = $conteos;
        $entran = self::plural($c['tareas'], 'tarea', 'tareas').' y '.self::plural($c['documentos'], 'documento', 'documentos');
        $completadas = $c['completadas'] === 1 ? '1 completada no se importa' : "{$c['completadas']} completadas no se importan";

        return $ctx->confirmada()
            ? "{$entran} guardados, {$c['ya_importadas']} ya estaban, {$completadas}, {$c['con_faltantes']} con faltantes. Informe: {$informe}"
            : "{$c['filas']} filas: {$entran} entran".($c['ya_importadas'] > 0 ? ", {$c['ya_importadas']} ya importadas" : '')
                .", {$completadas}, {$c['con_faltantes']} con faltantes. Informe: {$informe}";
    }

    /** RN-IMP-07: una transacción por fila; lo rechazado son faltantes y un error inesperado deja solo esa fila afuera. */
    private function guardar(TareaDelLote $tarea, Contexto $ctx): Resultado
    {
        try {
            return $this->db->transaction(function () use ($tarea, $ctx): Resultado {
                [$tipo, $id] = $tarea->documento !== null && $tarea->obraId !== null
                    ? ['obra_documento', $this->obras->agregarDocumento($ctx->actorId, $tarea->obraId, $tarea->documento)]
                    : ['pedido', $this->tareas->crear($ctx->actorId, $tarea->tarea ?? throw CargaRechazada::por(T::ERROR_INESPERADO_FILA))];
                $ctx->registrar([new FilaDeCorrida(C::HOJA_TAREAS, $tarea->clave, Resultado::Importada, $tipo, $id)]);

                return Resultado::Importada;
            });
        } catch (CargaRechazada $e) {
            foreach ($e->motivos as $motivo) {
                $tarea->faltantes[] = new Faltante(C::HOJA_TAREAS, $tarea->fila, $tarea->clave, C::TAREA, $motivo, Informe::ADMINISTRACION);
            }
        } catch (Throwable $e) { // RN-IMP-07: cualquier error de una fila la deja afuera y la corrida sigue.
            $tarea->faltantes[] = new Faltante(C::HOJA_TAREAS, $tarea->fila, $tarea->clave, C::CLAVE, T::ERROR_INESPERADO_FILA, Informe::ADMINISTRACION);
            $ctx->errorInesperado(C::HOJA_TAREAS, $tarea->fila, $e, "Fila {$tarea->fila}: error inesperado, no se guardó nada de esta fila (ver informe).");
        }

        return Resultado::Faltante;
    }

    /** Los faltantes van a "Faltantes" y a "Para completar por sector", con el sector que los completa (RN-IMP-33). */
    private function informar(Informe $informe, TareaDelLote $tarea): void
    {
        $loCompleta = $tarea->sector === Informe::ADMINISTRACION ? Informe::ADMINISTRACION : "el sector {$tarea->sector}";

        foreach ($tarea->faltantes as $f) {
            $informe->faltante(new Faltante($f->hoja, $f->fila, $f->sujeto, $f->columna, $f->que, $loCompleta), $tarea->sector, $tarea->texto, $tarea->obraEscrita);
        }

        array_push($informe->avisos, ...$tarea->avisos);
    }

    /**
     * @param  list<FilaLeida>  $filas
     * @return array<string, list<int>>
     */
    private static function porClave(array $filas): array
    {
        $porClave = [];

        foreach ($filas as $fila) {
            $clave = $fila->texto(C::CLAVE);

            if ($clave !== null) {
                $porClave[$clave][] = $fila->numero;
            }
        }

        return $porClave;
    }

    /**
     * @param  list<FilaLeida>  $filas
     * @return array<string, list<int>>
     */
    private static function repetidas(array $filas): array
    {
        return array_filter(self::porClave($filas), static fn (array $numeros): bool => count($numeros) > 1);
    }

    /**
     * @param  list<int>  $numeros
     */
    private static function enumerar(array $numeros): string
    {
        $ultimo = array_pop($numeros);

        return $numeros === [] ? (string) $ultimo : implode(', ', $numeros)." y {$ultimo}";
    }

    private static function plural(int $n, string $uno, string $varios): string
    {
        return $n === 1 ? "1 {$uno}" : "{$n} {$varios}";
    }
}
