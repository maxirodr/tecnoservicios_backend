<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Importacion\Application\FechaEmpresa;
use App\Modules\Importacion\Application\Ports\EscritorDeInforme;
use App\Modules\Importacion\Application\Ports\LectorDePlantillas;
use App\Modules\Importacion\Domain\EstadoCorrida;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lectura\PlantillaInvalida;
use App\Modules\Importacion\Domain\Modo;
use App\Shared\Actividad\CargaInicialEnCurso;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Una corrida de un lote (`importacion.md` §6.1, §11.1): el archivo de la carpeta (RN-IMP-12), una a la vez
 * (RN-IMP-06), su huella (RN-IMP-05), quién aprueba y el ensayo previo (RN-IMP-04), el **ensayo** dentro de una
 * transacción que se deshace (RN-IMP-03), el registro de la corrida y el informe. La consola solo dice conteos, la
 * huella corta y la ruta del informe (RN-IMP-13).
 */
final readonly class CorrerCarga
{
    public const OTRA_EN_CURSO = 'Hay otra carga en curso. Esperá a que termine.';

    public const BLOQUEO = 'importacion';

    public function __construct(
        private CarpetaDeImportacion $carpeta,
        private QuienAprueba $aprueba,
        private RegistroDeCorridas $corridas,
        private LectorDePlantillas $lector,
        private EscritorDeInforme $escritor,
        private HojasDelLote $hojas,
        private CargaInicialEnCurso $cargaInicial,
        private Connection $db,
        private Repository $cache,
        private LoggerInterface $log,
    ) {}

    public function handle(ImportadorDeLote $lote, string $archivo, bool $confirmar, ?string $aprobo): Salida
    {
        $ruta = $this->carpeta->archivo($archivo);

        if ($ruta === null) {
            return Salida::noEmpezo(CarpetaDeImportacion::FUERA);
        }

        $store = $this->cache->getStore();
        $bloqueo = $store instanceof LockProvider ? $store->lock(self::BLOQUEO, config()->integer('importacion.bloqueo_segundos')) : null;

        if ($bloqueo !== null && ! $bloqueo->get()) {
            return Salida::noEmpezo(self::OTRA_EN_CURSO);
        }

        try {
            return $this->correr($lote, $ruta, $confirmar, $aprobo);
        } finally {
            $bloqueo?->release();
        }
    }

    private function correr(ImportadorDeLote $lote, string $ruta, bool $confirmar, ?string $aprobo): Salida
    {
        $this->corridas->marcarInterrumpidas();
        $huella = (string) hash_file('sha256', $ruta);
        $modo = $confirmar ? Modo::Confirmada : Modo::Ensayo;
        $quien = $this->aprueba->quien($lote->lote(), $confirmar, $aprobo);

        if (is_string($quien)) {
            return Salida::noEmpezo("No se guardó nada: {$quien}.");
        }

        if ($confirmar && ! $this->corridas->hayEnsayo($lote->lote(), $huella)) {
            return Salida::noEmpezo('No se guardó nada: primero corré el ensayo de este archivo.');
        }

        try {
            $hojas = $this->lector->leer($ruta, $this->hojas->de($lote->lote()));
        } catch (PlantillaInvalida $e) {
            return Salida::noEmpezo($e->getMessage());
        }

        $corrida = $this->corridas->empezar($lote->lote(), $modo, $huella, $confirmar ? $quien->id : null);
        $informe = new Informe($lote->lote(), $modo, $huella, FechaEmpresa::ahora()->format('d/m/Y H:i'), $confirmar ? $quien->nombre : null);
        $ctx = new Contexto($corrida->id, $quien->id, $modo, $informe, $this->corridas, $this->log);
        $conteos = $this->cargaInicial->durante($quien->id, fn (): array => $confirmar ? $lote->correr($hojas, $ctx) : $this->ensayar($lote, $hojas, $ctx));

        $nombre = $this->carpeta->informe($lote->lote(), $modo);
        $this->escritor->escribir($informe, $this->carpeta->ruta($nombre));
        $this->corridas->terminar($corrida, $ctx->conErrores ? EstadoCorrida::ConErrores : EstadoCorrida::Terminada, $conteos, $nombre);
        $cabeza = $confirmar ? "Carga confirmada (aprobó {$quien->nombre})." : 'Ensayo: no se guardó nada.';

        return new Salida(! $ctx->conErrores, [...$ctx->consola, "{$cabeza} ".$lote->mensaje($conteos, $ctx, $this->carpeta->ruta($nombre))], $informe->huellaCorta());
    }

    /**
     * RN-IMP-03: por los mismos caminos que la carga real, dentro de una transacción que se deshace al final: no
     * queda ni un dato, ni una actividad, ni un evento (los de los módulos salen después del commit), ni un aviso.
     *
     * @param  array<string, list<FilaLeida>>  $hojas
     * @return array<string, int>
     */
    private function ensayar(ImportadorDeLote $lote, array $hojas, Contexto $ctx): array
    {
        $this->db->beginTransaction();

        try {
            return $lote->correr($hojas, $ctx);
        } finally {
            $this->db->rollBack();
            $ctx->registrarPendientes();
        }
    }
}
