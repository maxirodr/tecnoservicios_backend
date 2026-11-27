<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Plantilla;

use App\Modules\Importacion\Domain\Lectura\Valores;
use App\Modules\Importacion\Domain\Lote;

/**
 * Las hojas de cada lote (`importacion.md` §5) y los valores de sus listas, en palabras del cliente y con la clave
 * del sistema. Los rubros (13 en Rubros, 12 columnas en Presupuestos) y los sectores vienen del sistema.
 */
final class Plantillas
{
    public const SIN_OBRA = 'Sin obra';

    public const EN_EL_SECTOR = '(en el sector)';

    public const ESTADOS_OBRA = ['En ejecución' => 'en_ejecucion', 'Confirmada' => 'confirmada'];

    public const APROBACIONES = ['Orden de compra' => 'oc', 'Orden de trabajo' => 'ot', 'Contrato' => 'contrato', 'Mail de aprobación' => 'mail'];

    public const MODOS = ['Por rubro' => 'por_rubro', 'Solo total' => 'solo_total'];

    public const SI_NO = ['Sí', 'No'];

    public const QUE_ES = ['Tarea' => 'tarea', 'Documento' => 'documento'];

    /** P3 nunca se usó: se calcula (RN-IMP-35). */
    public const PRIORIDADES = ['Urgente' => 'urgente', 'Prioritario' => 'prioritario', 'Normal' => 'normal', 'P3' => null];

    public const ESTADOS_TAREA = ['No iniciada' => 'no_iniciada', 'En curso' => 'en_curso', 'Bloqueada' => 'bloqueada', 'Completada' => 'completada'];

    public const TIPOS_DOCUMENTO = ['Certificado' => 'certificado', 'Otro' => 'otro'];

    /**
     * @param  list<string>  $rubros  los 13 nombres, en orden
     * @param  list<string>  $presupuestables  los 12 (sin EPP)
     * @param  list<string>  $sectores  los activos
     * @return list<Hoja>
     */
    public static function de(Lote $lote, array $rubros, array $presupuestables, array $sectores): array
    {
        return $lote === Lote::Obras ? self::obras($rubros, $presupuestables) : self::tareas($sectores);
    }

    /**
     * El valor del sistema para lo escrito en una lista, sin mayúsculas ni tildes; `false` si no es de la lista.
     *
     * @param  array<string, string|null>  $lista
     */
    public static function valorDe(array $lista, ?string $escrito): string|false|null
    {
        foreach ($lista as $texto => $valor) {
            if (Valores::clave($texto) === Valores::clave($escrito)) {
                return $valor;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $rubros
     * @param  list<string>  $presupuestables
     * @return list<Hoja>
     */
    private static function obras(array $rubros, array $presupuestables): array
    {
        $c = Columnas::class;

        return [
            new Hoja(Columnas::HOJA_OBRAS, true, [
                $c::NUMERO, $c::REVISION, $c::TRABAJO, $c::CLIENTE, $c::PLANTA, $c::RESPONSABLE, $c::ESTADO, $c::INICIO, $c::FIN,
                $c::DRIVE, $c::OFERTA, $c::SOLICITANTE_NOMBRE, $c::SOLICITANTE_MAIL, $c::SOLICITANTE_TELEFONO,
                $c::COMPRADOR_NOMBRE, $c::COMPRADOR_MAIL, $c::COMPRADOR_TELEFONO, $c::CORTE, $c::TOTAL_PLANILLA,
            ], [
                $c::NUMERO, $c::REVISION, $c::TRABAJO, $c::CLIENTE, $c::RESPONSABLE, $c::ESTADO, $c::OFERTA,
                $c::SOLICITANTE_NOMBRE, $c::SOLICITANTE_MAIL, $c::SOLICITANTE_TELEFONO,
                $c::COMPRADOR_NOMBRE, $c::COMPRADOR_MAIL, $c::COMPRADOR_TELEFONO, $c::CORTE,
            ], [$c::ESTADO => array_keys(self::ESTADOS_OBRA)]),
            new Hoja(Columnas::HOJA_PRESUPUESTOS, false, [
                $c::OBRA, $c::NUMERO, $c::REVISION, $c::DESCRIPCION, $c::OFERTA_ADICIONAL, $c::APROBACION_TIPO,
                $c::APROBACION_NUMERO, $c::APROBACION_LINK, $c::MODO, ...$presupuestables, $c::TOTAL, $c::MONTO_OBRA,
            ], [$c::OBRA, $c::NUMERO], [$c::APROBACION_TIPO => array_keys(self::APROBACIONES), $c::MODO => array_keys(self::MODOS)]),
            new Hoja(Columnas::HOJA_GASTADO, false, [$c::OBRA, $c::RUBRO_ESCRITO, $c::GASTADO], [$c::OBRA, $c::RUBRO_ESCRITO, $c::GASTADO]),
            new Hoja(Columnas::HOJA_RUBROS, false, [$c::COMO_FIGURA, $c::RUBRO_SISTEMA, $c::APROBADO, $c::APROBO], [
                $c::COMO_FIGURA, $c::RUBRO_SISTEMA, $c::APROBADO, $c::APROBO,
            ], [$c::RUBRO_SISTEMA => $rubros, $c::APROBADO => self::SI_NO]),
            new Hoja(Columnas::HOJA_MATERIALES, false, [
                $c::OBRA, $c::MATERIAL, $c::UNIDAD, $c::CANTIDAD, $c::PRECIO, $c::PRESUPUESTO, $c::RETIRADO,
            ], [$c::OBRA, $c::MATERIAL, $c::UNIDAD, $c::CANTIDAD, $c::PRESUPUESTO]),
        ];
    }

    /**
     * @param  list<string>  $sectores
     * @return list<Hoja>
     */
    private static function tareas(array $sectores): array
    {
        $c = Columnas::class;

        return [
            new Hoja(Columnas::HOJA_TAREAS, true, [
                $c::CLAVE, $c::QUE_ES, $c::OBRA, $c::TAREA, $c::CON_QUIEN, $c::PRIORIDAD, $c::PROPIETARIO, $c::SECTOR,
                $c::SOLICITANTE, $c::ESTADO, $c::FECHA_PEDIDO, $c::FECHA_ENTREGA, $c::PERIODO_INICIO, $c::PERIODO_FIN,
                $c::LINKS, $c::NOTAS, $c::TIPO_DOCUMENTO, $c::FECHA_DOCUMENTO,
            ], [
                $c::CLAVE, $c::QUE_ES, $c::OBRA, $c::TAREA, $c::PROPIETARIO, $c::SECTOR, $c::ESTADO, $c::FECHA_ENTREGA,
                $c::NOTAS, $c::TIPO_DOCUMENTO, $c::FECHA_DOCUMENTO,
            ], [
                $c::QUE_ES => array_keys(self::QUE_ES), $c::PRIORIDAD => array_keys(self::PRIORIDADES), $c::SECTOR => $sectores,
                $c::ESTADO => array_keys(self::ESTADOS_TAREA), $c::TIPO_DOCUMENTO => array_keys(self::TIPOS_DOCUMENTO),
            ]),
            new Hoja(Columnas::HOJA_PERSONAS, false, [$c::COMO_FIGURA_MINUTA, $c::MAIL_USUARIO], [$c::COMO_FIGURA_MINUTA, $c::MAIL_USUARIO]),
        ];
    }
}
