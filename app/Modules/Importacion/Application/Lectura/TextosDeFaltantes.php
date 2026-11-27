<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Lectura;

use App\Modules\Importacion\Domain\Plantilla\Columnas as C;
use App\Shared\Domain\Importe;

/** Los textos de la columna "Qué falta" y de los avisos (`importacion.md` §8), en palabras del cliente. */
final class TextosDeFaltantes
{
    private const FALTA = [
        C::NUMERO => 'Falta el N.º de presupuesto.',
        C::REVISION => 'Falta la revisión.',
        C::TRABAJO => 'Falta el trabajo.',
        C::CLIENTE => 'Falta el cliente.',
        C::RESPONSABLE => 'Falta el mail del responsable de obra.',
        C::ESTADO => 'Falta el estado.',
        C::OFERTA => 'Falta el link de la oferta técnica.',
        C::SOLICITANTE_NOMBRE => 'Falta el nombre del solicitante en planta.',
        C::SOLICITANTE_MAIL => 'Falta el mail del solicitante en planta.',
        C::SOLICITANTE_TELEFONO => 'Falta el teléfono del solicitante en planta.',
        C::COMPRADOR_NOMBRE => 'Falta el nombre del comprador.',
        C::COMPRADOR_MAIL => 'Falta el mail del comprador.',
        C::COMPRADOR_TELEFONO => 'Falta el teléfono del comprador.',
        C::CORTE => 'Falta la fecha de corte.',
        C::OBRA => 'Falta la obra.',
        C::TOTAL => 'Falta el total.',
        C::RUBRO_ESCRITO => 'Falta el rubro.',
        C::GASTADO => 'Falta el gastado hasta el corte.',
        C::MATERIAL => 'Falta el material.',
        C::UNIDAD => 'Falta la unidad.',
        C::CANTIDAD => 'Falta la cantidad presupuestada.',
        C::PRESUPUESTO => 'Falta el presupuesto.',
        C::CLAVE => 'Falta la clave.',
        C::QUE_ES => 'Falta qué es: Tarea o Documento.',
        C::TAREA => 'Falta la tarea.',
        C::FECHA_ENTREGA => 'Falta la fecha de entrega.',
        C::TIPO_DOCUMENTO => 'Falta el tipo de documento.',
        C::FECHA_DOCUMENTO => 'Un certificado necesita su fecha.',
    ];

    public const ESTADO_OBRA = 'El estado tiene que ser En ejecución o Confirmada.';

    public const CORTE_FUTURO = 'La fecha de corte no puede ser posterior a hoy.';

    public const GASTADO_NEGATIVO = 'El gastado no puede ser negativo.';

    public const MEZCLADO = 'Un presupuesto se carga por rubro o con el total, no las dos cosas.';

    public const PONE_EL_ESTADO = 'Poné el estado.';

    public const FALTA_PROPIETARIO = "Falta el propietario: una persona o '(en el sector)' con el sector.";

    public const DOCUMENTO_SIN_LINK = 'Un documento necesita al menos un link.';

    public const DOCUMENTO_SIN_OBRA = 'Un documento tiene que ser de una obra.';

    public const OBRA_EXISTENTE = 'La obra ya está en el sistema: sus datos y presupuestos no se tocan; si falta algo, se completa en la ficha y en W-13.';

    public const SIN_PRESUPUESTO_POR_RUBRO = 'La obra no tiene presupuesto por rubro: la rendición va a mostrar lo gastado sin compararlo por rubro.';

    public const FILA_COMPLETADA = 'Fila Completada: no se importa.';

    public const ERROR_INESPERADO_OBRA = 'Error inesperado: no se guardó nada de esta obra.';

    public const ERROR_INESPERADO_FILA = 'Error inesperado: no se guardó nada de esta fila.';

    public static function falta(string $columna): string
    {
        return self::FALTA[$columna] ?? "Falta {$columna}.";
    }

    public static function sinUsuario(string $mail): string
    {
        return "No hay un usuario activo con el mail {$mail}.";
    }

    public static function rubroSinEquivalencia(string $escrito): string
    {
        return "El rubro '{$escrito}' no tiene equivalencia aprobada en la hoja Rubros.";
    }

    public static function equivalenciaNo(string $escrito): string
    {
        return "La equivalencia de '{$escrito}' dice 'No': ese gastado no entra hasta que se decida su rubro.";
    }

    /**
     * @param  numeric-string  $suma
     * @param  numeric-string  $planilla
     */
    public static function controlDistinto(string $suma, string $planilla): string
    {
        $diferencia = ltrim(bcsub($planilla, $suma, 2), '-');

        return 'La suma por rubro ('.self::importe($suma).') no da el total de su planilla ('.self::importe($planilla)
            .'): diferencia '.self::importe($diferencia).'.';
    }

    public static function dobleConteo(int $renglones, string $corte): string
    {
        return "La obra ya tiene {$renglones} renglones cargados con fecha hasta el {$corte}: se contarían dos veces. Anulalos o cargá el acumulado sin ellos.";
    }

    public static function cargaVigente(string $que, string $aprobo, string $el): string
    {
        return "La obra ya tiene su carga inicial de {$que} (aprobó {$aprobo} el {$el}). Para cargarla de nuevo, anulá esa primero.";
    }

    /** "1.250.000,50" · "80.000" (como `Importe::legible`). */
    public static function importe(string $valor): string
    {
        return Importe::desde($valor)->legible();
    }

    /** "96" · "12,5" · "−10" (cantidades de la lista, con el signo menos de W-48). */
    public static function cantidad(string $valor): string
    {
        $negativo = str_starts_with($valor, '-');
        [$enteros, $decimales] = array_pad(explode('.', ltrim($valor, '-')), 2, '');
        $decimales = rtrim($decimales, '0');
        $texto = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $enteros).($decimales === '' ? '' : ",{$decimales}");

        return $negativo && $texto !== '0' ? "−{$texto}" : $texto;
    }

    /** "dd/mm/aaaa" de "AAAA-MM-DD". */
    public static function fecha(string $fecha): string
    {
        [$anio, $mes, $dia] = explode('-', substr($fecha, 0, 10));

        return "{$dia}/{$mes}/{$anio}";
    }
}
