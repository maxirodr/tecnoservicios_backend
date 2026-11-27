<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Tareas;

use App\Modules\Identidad\Contracts\UsuariosConsulta;
use App\Modules\Importacion\Application\FechaEmpresa;
use App\Modules\Importacion\Application\Lectura\RevisorDeFila;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Domain\Informe\Aviso;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;
use App\Modules\Importacion\Domain\Plantilla\Plantillas;
use App\Modules\Obras\Contracts\CargaInicialDeObras;
use App\Modules\Obras\Contracts\DocumentoInicial;
use App\Modules\Pedidos\Contracts\CargaInicialDeTareas;
use App\Modules\Pedidos\Contracts\TareaInicial;
use App\Shared\CargaInicial\CargaRechazada;

/**
 * Hoja Tareas (§5.6, RN-IMP-32…38): cada fila es una tarea abierta o un documento de la obra, con **todos** sus
 * faltantes y el sector que los completa. Las Completadas no se importan; las Claves ya importadas no se tocan.
 */
final readonly class LeerTareas
{
    public const LARGO_CLAVE = 120;

    private const LARGO_TAREA = 2000;

    private const LARGO_DOCUMENTO = 120;

    private const LARGO_CON_QUIEN = 150;

    private const LARGO_NOTAS = 5000;

    private const LARGO_MOTIVO = 2000;

    private const MAXIMO_LINKS = 10;

    public function __construct(
        private CargaInicialDeObras $obras,
        private CargaInicialDeTareas $tareas,
        private UsuariosConsulta $usuarios,
        private PersonasDeLaMinuta $personas,
    ) {}

    /**
     * La hoja Personas, antes de leer las filas (RN-IMP-15).
     *
     * @param  list<FilaLeida>  $filas
     */
    public function personas(array $filas): void
    {
        $this->personas->cargar($filas);
    }

    public function fila(FilaLeida $fila, Informe $informe): TareaDelLote
    {
        $r = new RevisorDeFila(C::HOJA_TAREAS, $fila, $fila->texto(C::CLAVE) ?? "fila {$fila->numero}", Informe::ADMINISTRACION);
        $clave = $r->texto(C::CLAVE, true, self::LARGO_CLAVE);
        $tipo = $r->lista(C::QUE_ES, Plantillas::QUE_ES, 'Qué es tiene que ser Tarea o Documento.', true);
        $tarea = new TareaDelLote($clave ?? $r->sujeto, $fila->numero, is_string($tipo) ? $tipo : null, $fila->texto(C::OBRA) ?? '', $fila->texto(C::TAREA) ?? '', Informe::ADMINISTRACION);
        $obraId = $this->obra($r, $tipo === TareaDelLote::DOCUMENTO);

        if ($tipo === TareaDelLote::DOCUMENTO) {
            $this->documento($r, $tarea, $obraId);
        } elseif ($tipo === TareaDelLote::TAREA) {
            $this->tarea($r, $tarea, $obraId, $informe);
        }

        $tarea->faltantes = $r->faltantes;

        return $tarea;
    }

    private function tarea(RevisorDeFila $r, TareaDelLote $t, ?int $obraId, Informe $informe): void
    {
        $estado = $r->lista(C::ESTADO, Plantillas::ESTADOS_TAREA, 'El estado tiene que ser No iniciada, En curso, Bloqueada o Completada.');

        if ($estado === 'completada') {
            $t->completada = true;
            $t->avisos[] = new Aviso(C::HOJA_TAREAS, $r->fila->numero, $t->clave, T::FILA_COMPLETADA);
            $r->faltantes = [];

            return;
        }

        if ($estado === null) {
            $r->falta(C::ESTADO, T::PONE_EL_ESTADO);
        }

        $texto = $r->texto(C::TAREA, true, self::LARGO_TAREA);
        $conQuien = $r->texto(C::CON_QUIEN, false, self::LARGO_CON_QUIEN);
        $prioridad = $r->lista(C::PRIORIDAD, Plantillas::PRIORIDADES, 'La prioridad tiene que ser Urgente, Prioritario, Normal o P3.');
        [$propietarioId, $sectorId] = $this->propietario($r, $t, $estado === 'en_curso', $informe);
        $solicitanteId = $this->solicitante($r, $t, $informe);
        $pedida = $r->fecha(C::FECHA_PEDIDO);
        $entrega = $r->fecha(C::FECHA_ENTREGA, true);
        $notas = $r->texto(C::NOTAS, false, $estado === 'bloqueada' ? self::LARGO_MOTIVO : self::LARGO_NOTAS);
        $links = $this->links($r, false);

        if ($pedida !== null && $pedida > FechaEmpresa::hoy()) {
            $r->falta(C::FECHA_PEDIDO, 'La fecha en que se pidió no puede ser posterior a hoy.');
        }

        if ($estado === 'bloqueada' && $notas === null && $r->fila->texto(C::NOTAS) === null) {
            $r->falta(C::NOTAS, 'Está Bloqueada sin motivo: escribí el motivo en Notas.');
        }

        if (! $r->sinFaltantes() || ! is_string($estado) || $texto === null || $entrega === null || $prioridad === false) {
            return;
        }

        $this->avisarP3($r, $t, $pedida, $entrega);
        $t->obraId = $obraId;
        $t->tarea = new TareaInicial(
            $texto, $obraId, $propietarioId, $sectorId, $solicitanteId, $estado, $conQuien, $pedida, $entrega,
            $r->fecha(C::PERIODO_INICIO), $r->fecha(C::PERIODO_FIN), $prioridad, $notas, $links,
        );
    }

    /** RN-IMP-38: Certificado (con su fecha) u Otro, con 1 a 10 links, de una obra que está en el sistema. */
    private function documento(RevisorDeFila $r, TareaDelLote $t, ?int $obraId): void
    {
        $nombre = $r->texto(C::TAREA, true, self::LARGO_DOCUMENTO);
        $tipo = $r->lista(C::TIPO_DOCUMENTO, Plantillas::TIPOS_DOCUMENTO, 'El tipo de documento tiene que ser Certificado u Otro.', true);
        $fecha = $r->fecha(C::FECHA_DOCUMENTO, $tipo === 'certificado');
        $links = $this->links($r, true);

        if ($r->sinFaltantes() && $obraId !== null && $nombre !== null && ($tipo === 'certificado' || $tipo === 'otro') && $links !== []) {
            $t->obraId = $obraId;
            $t->documento = new DocumentoInicial($tipo, $nombre, $tipo === 'certificado' ? $fecha : null, $links);
        }
    }

    /** La obra en el sistema y no Terminada (RN-IMP-33), o "Sin obra" (no en un documento). */
    private function obra(RevisorDeFila $r, bool $esDocumento): ?int
    {
        $escrita = $r->texto(C::OBRA, true);

        if ($escrita === null) {
            return null;
        }

        if (mb_strtolower($escrita) === mb_strtolower(Plantillas::SIN_OBRA)) {
            if ($esDocumento) {
                $r->falta(C::OBRA, T::DOCUMENTO_SIN_OBRA);
            }

            return null;
        }

        try {
            $numero = $this->obras->numero($escrita);
        } catch (CargaRechazada) {
            $numero = $escrita;
        }

        $obraId = $this->obras->existe($numero);

        if ($obraId === null) {
            $r->falta(C::OBRA, "La obra {$numero} no está en el sistema: cargala en la hoja Obras o pasá la tarea a 'Sin obra' nombrando la obra en la tarea.");
        } elseif (! $esDocumento && $this->obras->estado($obraId) === 'terminada') {
            $r->falta(C::OBRA, "La obra {$numero} está Terminada: completá la tarea en la minuta o pasala a 'Sin obra' nombrando la obra en la tarea.");

            return null;
        }

        return $obraId;
    }

    /**
     * Una persona activa de un sector con Tareas ≥ Ver, o "(en el sector)" con un sector activo; En curso necesita
     * persona (RN-IMP-15, 33, 34). Deja el sector que completa los faltantes.
     *
     * @return array{?int, ?int}
     */
    private function propietario(RevisorDeFila $r, TareaDelLote $t, bool $enCurso, Informe $informe): array
    {
        $escrito = $r->texto(C::PROPIETARIO);
        $sectorEscrito = $r->texto(C::SECTOR);
        $sector = $sectorEscrito === null ? null : $this->usuarios->sectorPorNombre($sectorEscrito);
        $t->sector = $sector->nombre ?? $sectorEscrito ?? Informe::ADMINISTRACION;

        if ($escrito === null || (mb_strtolower($escrito) === Plantillas::EN_EL_SECTOR && $sectorEscrito === null)) {
            $r->falta(C::PROPIETARIO, T::FALTA_PROPIETARIO);

            return [null, null];
        }

        if (mb_strtolower($escrito) === Plantillas::EN_EL_SECTOR) {
            if ($enCurso) {
                $r->falta(C::PROPIETARIO, 'Una tarea En curso tiene que tener propietario: elegí quién la tiene.');
            }

            if ($sector === null || ! $sector->activo) {
                $r->falta(C::SECTOR, 'Elegí un sector activo.');
            } elseif (! $this->usuarios->sectorPuede($sector->id, 'pedidos', 'ver')) {
                $r->falta(C::SECTOR, "{$sector->nombre} no tiene acceso a Tareas: elegí una persona.");
            }

            return [null, $sector?->id];
        }

        $persona = $this->personas->buscar($escrito);

        if (is_string($persona)) {
            $r->falta(C::PROPIETARIO, $persona);
            $informe->personaPorRevisar($escrito, $r->fila->numero, $this->personas->sugerido($escrito));

            return [null, null];
        }

        $t->sector = $persona->sectorNombre;
        $motivo = $this->personas->motivoComoPropietario($persona);

        if ($motivo !== null) {
            $r->falta(C::PROPIETARIO, $motivo);
        }

        return [$persona->id, $persona->sectorId];
    }

    /** RN-IMP-15: un solicitante que no se encuentra no frena (queda "Solicitante: —"); uno inactivo se guarda igual. */
    private function solicitante(RevisorDeFila $r, TareaDelLote $t, Informe $informe): ?int
    {
        $escrito = $r->texto(C::SOLICITANTE);

        if ($escrito === null) {
            return null;
        }

        $persona = $this->personas->buscar($escrito);

        if (is_string($persona)) {
            $t->avisos[] = new Aviso(C::HOJA_TAREAS, $r->fila->numero, $t->clave, "No encontramos a '{$escrito}' en Personas: queda sin solicitante.");
            $informe->personaPorRevisar($escrito, $r->fila->numero, $this->personas->sugerido($escrito));

            return null;
        }

        return $persona->id;
    }

    /**
     * Hasta 10, uno por línea, solo http/https (FE-01); un documento necesita al menos uno.
     *
     * @return list<string>
     */
    private function links(RevisorDeFila $r, bool $obligatorio): array
    {
        $texto = $r->fila->texto(C::LINKS);
        $lineas = $texto === null ? [] : array_values(array_filter(explode("\n", $texto), static fn (string $l): bool => trim($l) !== ''));

        if ($lineas === [] && $obligatorio) {
            $r->falta(C::LINKS, T::DOCUMENTO_SIN_LINK);
        } elseif (count($lineas) > self::MAXIMO_LINKS) {
            $r->falta(C::LINKS, 'Hasta '.self::MAXIMO_LINKS.' links por fila.');
        }

        $validos = [];

        foreach ($lineas as $linea) {
            if (preg_match('/^https?:\/\/\S+$/i', trim($linea)) === 1) {
                $validos[] = trim($linea);
            } else {
                $r->falta(C::LINKS, 'El link tiene que empezar con http:// o https://.');
            }
        }

        return $validos;
    }

    /** "Prioridad 'P3': se calculó (Prioritario)." · "Prioridad 'P3': queda Normal." (§8, RN-IMP-35) */
    private function avisarP3(RevisorDeFila $r, TareaDelLote $t, ?string $pedida, string $entrega): void
    {
        if (mb_strtoupper((string) $r->fila->texto(C::PRIORIDAD)) !== 'P3') {
            return;
        }

        $calculada = $this->tareas->prioridadCalculada($pedida, $entrega);
        $t->avisos[] = new Aviso(C::HOJA_TAREAS, $r->fila->numero, $t->clave, $calculada === null
            ? "Prioridad 'P3': queda Normal."
            : "Prioridad 'P3': se calculó (".array_search($calculada, Plantillas::PRIORIDADES, true).').');
    }
}
