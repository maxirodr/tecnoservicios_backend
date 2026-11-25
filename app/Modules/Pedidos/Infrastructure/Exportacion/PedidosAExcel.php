<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Exportacion;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * El `.xlsx` de "Exportar a Excel" (W-24, resuelta DU-P16): las columnas de la tabla más N.º de
 * presupuesto, Con quién / para quién, Período, Aclaraciones y Completado, en el orden recibido. Escribe
 * fila por fila (OpenSpout), así lo filtrado entero no se carga en memoria. Las fechas van como fechas.
 */
final class PedidosAExcel
{
    public const NOMBRE_HOJA = 'Tareas';

    public const ENCABEZADOS = [
        'Obra', 'Tarea', 'Sector', 'Propietario', 'Solicitante', 'Pedida', 'Entrega', 'Prioridad', 'Estado',
        'N.º de presupuesto', 'Con quién / para quién', 'Período', 'Aclaraciones', 'Completado',
    ];

    private const SIN_DATO = '—';

    private const FORMATO_FECHA = 'dd/mm/yyyy';

    /**
     * @param  iterable<Pedido>  $pedidos
     */
    public function escribir(iterable $pedidos, string $destino): void
    {
        $writer = new Writer;
        $writer->openToFile($destino);
        $writer->getCurrentSheet()->setName(self::NOMBRE_HOJA);
        $writer->addRow(Row::fromValues(self::ENCABEZADOS, (new Style)->setFontBold()));
        $fecha = (new Style)->setFormat(self::FORMATO_FECHA);

        foreach ($pedidos as $pedido) {
            $writer->addRow(Row::fromValuesWithStyles(self::fila($pedido), null, [5 => $fecha, 6 => $fecha, 13 => $fecha]));
        }

        $writer->close();
    }

    /** `pedidos-AAAA-MM-DD.xlsx`, con el día de la empresa. */
    public static function nombreDeArchivo(): string
    {
        return 'tareas-'.FechaEmpresa::hoyTexto().'.xlsx';
    }

    /** Como en la tabla: el pedido en el sector, o la persona con "(inactivo)" si ya no está (RN-PED-60, PU-612). */
    private static function propietario(Pedido $p): string
    {
        if ($p->propietario === null) {
            return "{$p->sector->nombre} (sector)";
        }

        return $p->propietario->activo ? $p->propietario->nombre : "{$p->propietario->nombre} (inactivo)";
    }

    /**
     * @return list<DateTimeInterface|string>
     */
    private static function fila(Pedido $p): array
    {
        return [
            $p->obra->nombre_trabajo ?? PedidosQuery::SERVICIOS_GENERALES,
            $p->tarea,
            $p->sector->nombre,
            self::propietario($p),
            $p->solicitante->nombre ?? self::SIN_DATO,
            self::fecha($p->fecha_pedido),
            self::fecha($p->fecha_entrega),
            self::prioridad($p->prioridad),
            $p->estado->etiqueta(),
            $p->obra?->principal->numero ?? '',
            $p->con_quien ?? '',
            $p->periodo_inicio === null ? '' : $p->periodo_inicio->format('d/m/Y').' al '.$p->periodo_fin?->format('d/m/Y'),
            $p->aclaraciones ?? '',
            $p->completado_at === null ? '' : self::fecha(CarbonImmutable::instance($p->completado_at)->setTimezone(FechaEmpresa::zona())->startOfDay()),
        ];
    }

    private static function fecha(?DateTimeInterface $fecha): DateTimeInterface|string
    {
        return $fecha === null ? self::SIN_DATO : CarbonImmutable::parse($fecha->format('Y-m-d'));
    }

    private static function prioridad(Prioridad $prioridad): string
    {
        return match ($prioridad) {
            Prioridad::Urgente => 'Urgente',
            Prioridad::Prioritario => 'Prioritario',
            Prioridad::Normal => 'Normal',
        };
    }
}
