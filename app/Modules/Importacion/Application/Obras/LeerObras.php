<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

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
use App\Modules\Obras\Contracts\PresupuestoInicial;
use App\Shared\CargaInicial\CargaRechazada;

/**
 * Hoja Obras (§5.1, RN-IMP-18, 20): una `ObraDelLote` por fila, con los obligatorios de `obras.md` §4.1 revisados
 * todos juntos. El N.º principal es la clave de la obra en todo el archivo. Una obra que ya está en el sistema no se
 * revisa ni se toca (solo su gastado y sus materiales).
 */
final readonly class LeerObras
{
    private const LARGO_TRABAJO = 200;

    private const LARGO_CLIENTE = 150;

    private const LARGO_NOMBRE = 120;

    private const LARGO_TELEFONO = 50;

    public function __construct(
        private CargaInicialDeObras $obras,
        private UsuariosConsulta $usuarios,
    ) {}

    /**
     * @param  list<FilaLeida>  $filas
     * @return array<string, ObraDelLote> por N.º principal normalizado
     */
    public function handle(array $filas, Informe $informe): array
    {
        $obras = [];

        foreach ($filas as $fila) {
            $r = new RevisorDeFila(C::HOJA_OBRAS, $fila, $fila->texto(C::NUMERO) ?? "fila {$fila->numero}", Informe::ADMINISTRACION);
            $numero = $this->numero($r);

            if ($numero === null || isset($obras[$numero])) {
                if ($numero !== null) {
                    $r->falta(C::NUMERO, "La obra {$numero} está repetida en la hoja Obras (filas {$obras[$numero]->fila} y {$fila->numero}).");
                }
                array_push($informe->faltantes, ...$r->faltantes);

                continue;
            }

            $existente = $this->obras->existe($numero);
            $obra = new ObraDelLote($numero, $fila->numero, $fila->texto(C::TRABAJO) ?? '', $fila->texto(C::ESTADO) ?? '', $existente);
            $obra->corte = $this->corte($r);
            $obra->totalPlanilla = $r->importe(C::TOTAL_PLANILLA);

            if ($existente !== null) {
                $obra->avisos[] = new Aviso(C::HOJA_OBRAS, $fila->numero, $numero, T::OBRA_EXISTENTE);
            } else {
                $this->alta($r, $obra);
            }

            $obra->faltan($r->faltantes);
            $obras[$numero] = $obra;
        }

        return $obras;
    }

    private function numero(RevisorDeFila $r): ?string
    {
        $escrito = $r->texto(C::NUMERO, true);

        try {
            return $escrito === null ? null : $this->obras->numero($escrito);
        } catch (CargaRechazada $e) {
            $r->falta(C::NUMERO, $e->getMessage());

            return null;
        }
    }

    private function corte(RevisorDeFila $r): ?string
    {
        $corte = $r->fecha(C::CORTE);

        if ($corte !== null && $corte > FechaEmpresa::hoy()) {
            $r->falta(C::CORTE, T::CORTE_FUTURO);

            return null;
        }

        return $corte;
    }

    /** Los obligatorios de la obra nueva (RN-IMP-18); el presupuesto principal nace sin montos (los suma §5.2). */
    private function alta(RevisorDeFila $r, ObraDelLote $obra): void
    {
        $revision = $r->texto(C::REVISION, true);
        $trabajo = $r->texto(C::TRABAJO, true, self::LARGO_TRABAJO);
        $cliente = $r->texto(C::CLIENTE, true, self::LARGO_CLIENTE);
        $planta = $r->texto(C::PLANTA, false, self::LARGO_CLIENTE);
        $responsable = $this->responsable($r);
        $estado = $r->lista(C::ESTADO, Plantillas::ESTADOS_OBRA, T::ESTADO_OBRA);
        $inicio = $r->fecha(C::INICIO);
        $fin = $r->fecha(C::FIN);
        $drive = $r->link(C::DRIVE);
        $oferta = $r->link(C::OFERTA, true);
        $contactos = [
            'solicitante' => $this->contacto($r, C::SOLICITANTE_NOMBRE, C::SOLICITANTE_MAIL, C::SOLICITANTE_TELEFONO),
            'comprador' => $this->contacto($r, C::COMPRADOR_NOMBRE, C::COMPRADOR_MAIL, C::COMPRADOR_TELEFONO),
        ];

        if ($estado === null) {
            $r->falta(C::ESTADO, T::ESTADO_OBRA);
        }

        if ($inicio !== null && $fin !== null && $fin < $inicio) {
            $r->falta(C::FIN, 'El fin previsto no puede ser anterior al inicio.');
        }

        $revisionNormalizada = $this->revision($r, $revision);

        if (! $r->sinFaltantes() || $trabajo === null || $cliente === null || $responsable === null || ! is_string($estado)
            || $contactos['solicitante'] === null || $contactos['comprador'] === null) {
            return;
        }

        $obra->presupuestos = [new PresupuestoInicial($obra->numero, $revisionNormalizada, null, $oferta, null, null, null, 'sin_cargar', [], null, null)];
        $obra->alta = [
            'trabajo' => $trabajo, 'cliente' => $cliente, 'planta' => $planta, 'responsable' => $responsable,
            'en_ejecucion' => $estado === 'en_ejecucion', 'inicio' => $inicio, 'fin' => $fin, 'drive' => $drive,
            'contactos' => ['solicitante' => $contactos['solicitante'], 'comprador' => $contactos['comprador']],
        ];
    }

    private function revision(RevisorDeFila $r, ?string $revision): ?string
    {
        try {
            return $revision === null ? null : $this->obras->revision($revision);
        } catch (CargaRechazada $e) {
            $r->falta(C::REVISION, $e->getMessage());

            return null;
        }
    }

    /** Un usuario activo del sistema (obras.md §4.1): "No hay un usuario activo con el mail laura@ejemplo.com." */
    private function responsable(RevisorDeFila $r): ?int
    {
        $mail = $r->texto(C::RESPONSABLE, true);
        $usuario = $mail === null ? null : $this->usuarios->porMail($mail);

        if ($mail !== null && ($usuario === null || ! $usuario->activo)) {
            $r->falta(C::RESPONSABLE, T::sinUsuario($mail));

            return null;
        }

        return $usuario?->id;
    }

    /**
     * @return array{nombre: string, email: string, telefono: string}|null
     */
    private function contacto(RevisorDeFila $r, string $nombre, string $mail, string $telefono): ?array
    {
        $datos = [
            'nombre' => $r->texto($nombre, true, self::LARGO_NOMBRE),
            'email' => $r->mail($mail, true),
            'telefono' => $r->texto($telefono, true, self::LARGO_TELEFONO),
        ];

        return in_array(null, $datos, true) ? null : $datos;
    }
}
