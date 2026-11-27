<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Plantilla;

use App\Modules\Importacion\Domain\Lote;

/**
 * La hoja "Cómo completar" de cada plantilla (`importacion.md` §5), en palabras del cliente: qué va en cada columna,
 * si es obligatoria y con qué formato.
 */
final class ComoCompletar
{
    private const COMUNES = [
        ['Todas', 'Fila 1 = encabezados (no los cambies); desde la fila 2, una fila por dato. Las filas vacías no cuentan.'],
        ['Fechas', 'Como fecha de Excel o escritas dd/mm/aaaa (30/11/2026).'],
        ['Importes', 'Número de Excel o escritos con punto de miles y coma decimal (1.250.000,50), hasta 2 decimales, sin $.'],
        ['Cantidades', 'Hasta 3 decimales.'],
        ['Links', 'Empiezan con http:// o https://.'],
        ['Listas', "Elegí de la lista desplegable (están en la hoja Listas). 'Sí' / 'No' aceptan si, sí, no."],
    ];

    private const OBRAS = [
        ['Obras · N.º de presupuesto', 'Obligatorio. El del presupuesto principal (1-200-2026): es la clave de la obra en todas las hojas.'],
        ['Obras · Revisión, Trabajo, Cliente', 'Obligatorios. Revisión como REV01; Trabajo hasta 200 letras; Cliente hasta 150.'],
        ['Obras · Planta', 'Opcional, hasta 150 letras.'],
        ['Obras · Responsable de obra (mail)', 'Obligatorio. El mail de una persona que ya tiene usuario en el sistema.'],
        ['Obras · Estado', 'Obligatorio: En ejecución o Confirmada.'],
        ['Obras · Inicio previsto, Fin previsto', 'Opcionales. El fin no puede ser anterior al inicio.'],
        ['Obras · Carpeta de Drive, Oferta técnica (link)', 'La oferta técnica es obligatoria, como link (el archivo se sube después en la ficha).'],
        ['Obras · Solicitante en planta y Comprador', 'Obligatorios: nombre, mail y teléfono de cada uno.'],
        ['Obras · Fecha de corte', 'Obligatoria si la obra tiene gastado o retirado: el último día que tiene al día su rendición. No puede ser posterior a hoy.'],
        ['Obras · Total gastado según su planilla', 'Opcional, para controlar: la suma de la hoja Gastado tiene que dar igual al centavo.'],
        ['Presupuestos', 'Una fila por presupuesto adicional, y el principal solo si trae montos u OC. Obra = el N.º principal.'],
        ['Presupuestos · Cómo se carga', 'Por rubro (las 12 columnas de rubros; vacío = 0) o Solo total (la columna Total). No las dos cosas.'],
        ['Presupuestos · Documento de aprobación, N.º de OC/OT, Link', 'Opcionales.'],
        ['Presupuestos · Monto del presupuesto', 'Opcional.'],
        ['Gastado', 'Una fila por obra y rubro, como su hoja General, con el gastado hasta la fecha de corte.'],
        ['Gastado · Rubro como figura en su planilla', "Tal cual está escrito ('INSUMO', 'LOGÍSTICA'). Si no es el nombre exacto de un rubro, necesita su fila aprobada en la hoja Rubros."],
        ['Rubros', 'Una fila por cada forma distinta de escribir un rubro. Lo aprueba administración: solo se usa con Aprobado = Sí y quién aprobó.'],
        ['Materiales', 'La lista del control por cantidad: Material (no repetido en la obra), Unidad, Cantidad presupuestada y, opcional, Precio unitario.'],
        ['Materiales · Presupuesto', 'Obligatorio si la obra tiene más de un presupuesto.'],
        ['Materiales · Retirado hasta el corte', 'Opcional, en la unidad del material. No suma plata: el importe ya está en el gastado.'],
    ];

    private const TAREAS = [
        ['Tareas · Clave', "Obligatoria y única: solapa + fila de la minuta ('2-131-2026 · fila 12')."],
        ['Tareas · Qué es', 'Tarea (una tarea abierta) o Documento (certificado, OC u otro documento de la obra).'],
        ['Tareas · Obra', "El N.º de presupuesto principal de una obra que ya está en el sistema, o 'Sin obra'. Un documento siempre es de una obra."],
        ['Tareas · Tarea', 'Obligatoria. En un documento, su nombre (hasta 120 letras).'],
        ['Tareas · Con quién', 'Opcional (la columna PROVEEDOR de la minuta), hasta 150 letras.'],
        ['Tareas · Prioridad', 'Urgente, Prioritario o Normal. Vacía o P3: se calcula si están las dos fechas; si no, Normal.'],
        ['Tareas · Propietario', "Obligatorio en una tarea: como figura en la minuta (nombre o mail) o '(en el sector)' con el Sector. Una En curso necesita persona."],
        ['Tareas · Solicitante', 'Opcional. Si no se encuentra, la tarea entra sin solicitante.'],
        ['Tareas · Estado', 'Obligatorio en una tarea: No iniciada, En curso, Bloqueada o Completada (las Completadas no se traen).'],
        ['Tareas · Fecha de pedido, Fecha de entrega', 'La entrega es obligatoria en una tarea; la fecha en que se pidió, opcional (no posterior a hoy).'],
        ['Tareas · Inicio y Fin del período', 'Opcionales: las dos o ninguna.'],
        ['Tareas · Links', 'Hasta 10, uno por línea dentro de la celda. Un documento necesita al menos uno.'],
        ['Tareas · Notas', 'Si está Bloqueada, el motivo (obligatorio); si no, las aclaraciones.'],
        ['Tareas · Tipo de documento, Fecha del documento', 'En un documento: Certificado (con su fecha) u Otro.'],
        ['Personas', 'Cómo figura cada uno en la minuta ("Cari", "Diego M.") y el mail de su usuario en el sistema.'],
    ];

    /**
     * @return list<array{string, string}>
     */
    public static function de(Lote $lote): array
    {
        return [...self::COMUNES, ...($lote === Lote::Obras ? self::OBRAS : self::TAREAS)];
    }
}
