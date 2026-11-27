<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Importacion\Application\Corridas\Contexto;
use App\Modules\Importacion\Application\Corridas\FilaDeCorrida;
use App\Modules\Importacion\Application\Corridas\ImportadorDeLote;
use App\Modules\Importacion\Application\Corridas\Rechazada;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;
use App\Modules\Importacion\Domain\Resultado;
use App\Modules\Obras\Contracts\CargaInicialDeObras;
use Illuminate\Database\Connection;
use Throwable;

/**
 * El lote obras (RN-IMP-02, 07, 19): lee las cinco hojas, junta los faltantes de cada obra y guarda cada obra sin
 * faltantes **en su propia transacción** (obra, presupuestos, montos, contactos, oferta, materiales, gastado y
 * retirado juntos o nada). Si una falla por un error inesperado, no queda nada de ella y la corrida sigue.
 */
final readonly class ImportarObras implements ImportadorDeLote
{
    public function __construct(
        private Connection $db,
        private CargaInicialDeObras $contrato,
        private LeerObras $obras,
        private LeerPresupuestos $presupuestos,
        private LeerGastado $gastado,
        private LeerMateriales $materiales,
        private GuardarObra $guardar,
        private ResumenDeObras $resumen,
    ) {}

    public function lote(): Lote
    {
        return Lote::Obras;
    }

    public function correr(array $hojas, Contexto $ctx): array
    {
        $informe = $ctx->informe;
        $lote = new LoteDeObras($this->obras->handle($hojas[C::HOJA_OBRAS] ?? [], $informe), $this->contrato);
        $this->presupuestos->handle($hojas[C::HOJA_PRESUPUESTOS] ?? [], $lote->obras, $informe);
        $this->gastado->handle($hojas[C::HOJA_GASTADO] ?? [], $hojas[C::HOJA_RUBROS] ?? [], $lote, $informe);
        $this->materiales->handle($hojas[C::HOJA_MATERIALES] ?? [], $lote, $informe);
        $conteos = ['obras' => count($lote->obras), 'guardadas' => 0, 'ya_estaban' => 0, 'con_faltantes' => 0, 'faltantes' => 0];

        foreach ($lote->obras as $obra) {
            RevisarObra::handle($obra);

            $resultado = $obra->entra() ? $this->guardar($obra, $ctx) : Resultado::Faltante;
            $conteos[match ($resultado) {
                Resultado::Importada => 'guardadas',
                Resultado::YaEstaba => 'ya_estaban',
                default => 'con_faltantes',
            }]++;
            $conteos['faltantes'] += count($obra->faltantes);
            array_push($informe->faltantes, ...$obra->faltantes);
            array_push($informe->avisos, ...$obra->avisos);
            $this->resumen->agregar($informe, $obra, $resultado);

            if ($resultado === Resultado::Faltante) {
                $ctx->registrar([new FilaDeCorrida(C::HOJA_OBRAS, $obra->numero, Resultado::Faltante)]);
            }
        }

        return $conteos;
    }

    public function mensaje(array $conteos, Contexto $ctx, string $informe): string
    {
        $c = $conteos;
        $yaEstaban = $c['ya_estaban'] > 0 ? ", {$c['ya_estaban']} ya estaban" : '';

        return $ctx->confirmada()
            ? "{$c['guardadas']} obras guardadas, {$c['ya_estaban']} ya estaban, {$c['con_faltantes']} con faltantes. Informe: {$informe}"
            : "{$c['obras']} obras: {$c['guardadas']} entran{$yaEstaban}, {$c['con_faltantes']} con faltantes ({$c['faltantes']}). Informe: {$informe}";
    }

    /**
     * RN-IMP-07: todo lo de la obra en una transacción. Lo que el módulo dueño rechaza son faltantes; un error
     * inesperado deja la obra afuera, se registra sin valores y la corrida sigue "con errores".
     */
    private function guardar(ObraDelLote $obra, Contexto $ctx): Resultado
    {
        try {
            return $this->db->transaction(function () use ($obra, $ctx): Resultado {
                $filas = $this->guardar->handle($ctx->actorId, $obra);
                $ctx->registrar($filas);

                return in_array(Resultado::Importada, array_map(static fn (FilaDeCorrida $f): Resultado => $f->resultado, $filas), true)
                    ? Resultado::Importada
                    : Resultado::YaEstaba;
            });
        } catch (Rechazada $e) {
            array_push($obra->faltantes, ...$e->faltantes);
        } catch (Throwable $e) { // RN-IMP-07: cualquier error de una obra la deja afuera y la corrida sigue.
            $obra->faltantes[] = new Faltante(C::HOJA_OBRAS, $obra->fila, $obra->numero, C::NUMERO, T::ERROR_INESPERADO_OBRA, Informe::ADMINISTRACION);
            $ctx->errorInesperado(C::HOJA_OBRAS, $obra->fila, $e, "Obra {$obra->numero}: error inesperado, no se guardó nada de esta obra (ver informe).");
        }

        return Resultado::Faltante;
    }
}
