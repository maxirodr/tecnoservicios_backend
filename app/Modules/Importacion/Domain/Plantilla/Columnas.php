<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Plantilla;

/** Nombres exactos de hojas y columnas de las plantillas (`importacion.md` §5.1…§5.7). */
final class Columnas
{
    public const HOJA_OBRAS = 'Obras';

    public const HOJA_PRESUPUESTOS = 'Presupuestos';

    public const HOJA_GASTADO = 'Gastado';

    public const HOJA_RUBROS = 'Rubros';

    public const HOJA_MATERIALES = 'Materiales';

    public const HOJA_TAREAS = 'Tareas';

    public const HOJA_PERSONAS = 'Personas';

    // §5.1 Obras
    public const NUMERO = 'N.º de presupuesto';

    public const REVISION = 'Revisión';

    public const TRABAJO = 'Trabajo';

    public const CLIENTE = 'Cliente';

    public const PLANTA = 'Planta';

    public const RESPONSABLE = 'Responsable de obra (mail)';

    public const ESTADO = 'Estado';

    public const INICIO = 'Inicio previsto';

    public const FIN = 'Fin previsto';

    public const DRIVE = 'Carpeta de Drive';

    public const OFERTA = 'Oferta técnica (link)';

    public const SOLICITANTE_NOMBRE = 'Solicitante en planta · nombre';

    public const SOLICITANTE_MAIL = 'Solicitante en planta · mail';

    public const SOLICITANTE_TELEFONO = 'Solicitante en planta · teléfono';

    public const COMPRADOR_NOMBRE = 'Comprador · nombre';

    public const COMPRADOR_MAIL = 'Comprador · mail';

    public const COMPRADOR_TELEFONO = 'Comprador · teléfono';

    public const CORTE = 'Fecha de corte';

    public const TOTAL_PLANILLA = 'Total gastado según su planilla';

    // §5.2 Presupuestos (y "Obra" en Gastado y Materiales)
    public const OBRA = 'Obra';

    public const DESCRIPCION = 'Descripción';

    public const OFERTA_ADICIONAL = 'Oferta técnica del adicional (link)';

    public const APROBACION_TIPO = 'Documento de aprobación';

    public const APROBACION_NUMERO = 'N.º de OC/OT';

    public const APROBACION_LINK = 'Link al documento de aprobación';

    public const MODO = 'Cómo se carga';

    public const TOTAL = 'Total';

    public const MONTO_OBRA = 'Monto del presupuesto';

    // §5.3 Gastado
    public const RUBRO_ESCRITO = 'Rubro como figura en su planilla';

    public const GASTADO = 'Gastado hasta el corte';

    // §5.4 Rubros
    public const COMO_FIGURA = 'Como figura en su planilla';

    public const RUBRO_SISTEMA = 'Rubro del sistema';

    public const APROBADO = 'Aprobado';

    public const APROBO = 'Aprobó';

    // §5.5 Materiales
    public const MATERIAL = 'Material';

    public const UNIDAD = 'Unidad';

    public const CANTIDAD = 'Cantidad presupuestada';

    public const PRECIO = 'Precio unitario presupuestado';

    public const PRESUPUESTO = 'Presupuesto';

    public const RETIRADO = 'Retirado hasta el corte';

    // §5.6 Tareas
    public const CLAVE = 'Clave';

    public const QUE_ES = 'Qué es';

    public const TAREA = 'Tarea';

    public const CON_QUIEN = 'Con quién';

    public const PRIORIDAD = 'Prioridad';

    public const PROPIETARIO = 'Propietario';

    public const SECTOR = 'Sector';

    public const SOLICITANTE = 'Solicitante';

    public const FECHA_PEDIDO = 'Fecha de pedido';

    public const FECHA_ENTREGA = 'Fecha de entrega';

    public const PERIODO_INICIO = 'Inicio del período';

    public const PERIODO_FIN = 'Fin del período';

    public const LINKS = 'Links';

    public const NOTAS = 'Notas';

    public const TIPO_DOCUMENTO = 'Tipo de documento';

    public const FECHA_DOCUMENTO = 'Fecha del documento';

    // §5.7 Personas
    public const COMO_FIGURA_MINUTA = 'Como figura en la minuta';

    public const MAIL_USUARIO = 'Mail del usuario en el sistema';
}
