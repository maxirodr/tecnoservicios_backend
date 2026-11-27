<?php

declare(strict_types=1);

namespace Tests\Feature\Importacion\Support;

use DateTimeImmutable;

/**
 * Las plantillas ficticias del caso de referencia (`importacion.md` §13, presupuestos de `rendicion.md` §9.1). Cada
 * test puede cambiarles una celda antes de escribirlas.
 */
trait PlantillasDelCasoDeReferencia
{
    /**
     * @param  array<string, string|null>  $cambios
     * @return array<string, string|int|float|DateTimeImmutable|null>
     */
    protected function filaObra(string $numero, string $trabajo, string $estado, ?string $corte, array $cambios = []): array
    {
        return [
            'N.º de presupuesto' => $numero, 'Revisión' => 'REV01', 'Trabajo' => $trabajo, 'Cliente' => 'Planta Ejemplo SA',
            'Planta' => 'Planta norte', 'Responsable de obra (mail)' => 'laura@ejemplo.com', 'Estado' => $estado,
            'Inicio previsto' => '01/10/2026', 'Fin previsto' => '30/12/2026', 'Carpeta de Drive' => 'https://drive.ejemplo.com/obra',
            'Oferta técnica (link)' => 'https://drive.ejemplo.com/oferta.pdf',
            'Solicitante en planta · nombre' => 'Rubén Ejemplo', 'Solicitante en planta · mail' => 'ruben@cliente.ejemplo.com',
            'Solicitante en planta · teléfono' => '291 400-0001',
            'Comprador · nombre' => 'Marta Ejemplo', 'Comprador · mail' => 'marta@cliente.ejemplo.com', 'Comprador · teléfono' => '291 400-0002',
            'Fecha de corte' => $corte, 'Total gastado según su planilla' => null,
            ...$cambios,
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    protected function hojasDeObras(): array
    {
        $principal = ['MATERIALES' => '5.000.000', 'INSUMOS' => '800.000', 'SUBCONTRATOS' => '1.200.000', 'LOGISTICA' => '300.000',
            'AMORTIZACION' => '200.000', 'OBRADORES/BAÑOS' => '150.000', 'MAQUINAS/HERRAMIENTAS' => '400.000', 'VIATICOS' => '600.000',
            'FIJOS' => '500.000', 'HORAS LIQUIDADAS' => '2.000.000'];

        return [
            'Obras' => [
                $this->filaObra('1-200-2026', 'Ampliación de nave de ensayo', 'En ejecución', '30/11/2026', ['Total gastado según su planilla' => '2.347.500,50']),
                $this->filaObra('2-131-2026', 'Piso industrial nave 3', 'En ejecución', '30/11/2026', ['Comprador · mail' => null]),
                $this->filaObra('2-140-2026', 'Reparación de losa tanque B', 'Confirmada', null),
            ],
            'Presupuestos' => [
                ['Obra' => '1-200-2026', 'N.º de presupuesto' => '1-200-2026', 'Cómo se carga' => 'Por rubro', ...$principal, 'Monto del presupuesto' => '15.000.000'],
                ['Obra' => '1-200-2026', 'N.º de presupuesto' => '1-200-2026 A1', 'Revisión' => 'REV01', 'Cómo se carga' => 'Solo total', 'Total' => 1000000, 'Monto del presupuesto' => 1300000],
            ],
            'Gastado' => [
                ['Obra' => '1-200-2026', 'Rubro como figura en su planilla' => 'MATERIALES', 'Gastado hasta el corte' => 1250000],
                ['Obra' => '1-200-2026', 'Rubro como figura en su planilla' => 'INSUMO', 'Gastado hasta el corte' => '80.000'],
                ['Obra' => '1-200-2026', 'Rubro como figura en su planilla' => 'INSUMOS', 'Gastado hasta el corte' => 45500.5],
                ['Obra' => '1-200-2026', 'Rubro como figura en su planilla' => 'EPP', 'Gastado hasta el corte' => '12.000,00'],
                ['Obra' => '1-200-2026', 'Rubro como figura en su planilla' => 'LOGÍSTICA', 'Gastado hasta el corte' => '60000'],
                ['Obra' => '1-200-2026', 'Rubro como figura en su planilla' => 'HORAS LIQUIDADAS', 'Gastado hasta el corte' => '900.000'],
                ['Obra' => '2-131-2026', 'Rubro como figura en su planilla' => 'VARIOS', 'Gastado hasta el corte' => '15.000'],
            ],
            'Rubros' => [
                ['Como figura en su planilla' => 'INSUMO', 'Rubro del sistema' => 'INSUMOS', 'Aprobado' => 'Sí', 'Aprobó' => 'Carla'],
                ['Como figura en su planilla' => 'LOGÍSTICA', 'Rubro del sistema' => 'LOGISTICA', 'Aprobado' => 'si', 'Aprobó' => 'Carla'],
            ],
            'Materiales' => [
                $this->filaMaterial('Cemento x 50 kg', 'bolsa', 150, '9.800', 96),
                $this->filaMaterial('Hormigón H-21', 'm³', 40, '100.000', 12),
                $this->filaMaterial('Malla sima', 'paño', 30, '21.000', 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filaMaterial(string $nombre, string $unidad, int|string $cantidad, string $precio, int|string|null $retirado): array
    {
        return ['Obra' => '1-200-2026', 'Material' => $nombre, 'Unidad' => $unidad, 'Cantidad presupuestada' => $cantidad,
            'Precio unitario presupuestado' => $precio, 'Presupuesto' => '1-200-2026', 'Retirado hasta el corte' => $retirado];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    protected function hojasDeTareas(): array
    {
        return [
            'Tareas' => [
                $this->filaTarea('1-200-2026 · fila 2', 'Documento', '1-200-2026', 'Certificado N°1', [
                    'Estado' => 'Completada', 'Links' => 'https://drive.ejemplo.com/certificado-1.pdf',
                    'Tipo de documento' => 'Certificado', 'Fecha del documento' => new DateTimeImmutable('2026-11-15'),
                ]),
                $this->filaTarea('1-200-2026 · fila 8', 'Tarea', '1-200-2026', 'Solicitud de materiales N°3', [
                    'Propietario' => 'Diego', 'Estado' => 'En curso', 'Prioridad' => 'PRIORITARIO', 'Fecha de entrega' => '03/12/2026',
                ]),
                $this->filaTarea('1-200-2026 · fila 9', 'Tarea', '1-200-2026', 'Alquiler de minipala', [
                    'Propietario' => 'Laura', 'Solicitante' => 'Diego', 'Estado' => 'Bloqueada', 'Notas' => 'esperando presupuesto',
                    'Fecha de pedido' => '20/11/2026', 'Fecha de entrega' => '25/11/2026',
                ]),
                $this->filaTarea('1-200-2026 · fila 10', 'Tarea', '1-200-2026', 'Pedido de andamios', [
                    'Propietario' => 'Diego', 'Estado' => 'Completada', 'Fecha de entrega' => '10/11/2026',
                ]),
                $this->filaTarea('1-200-2026 · fila 11', 'Tarea', '1-200-2026', 'Cotizar volquete', ['Propietario' => 'Diego', 'Estado' => 'No iniciada']),
                $this->filaTarea('1-200-2026 · fila 12', 'Tarea', '1-200-2026', 'Pasar facturas', [
                    'Propietario' => 'Cari', 'Estado' => 'No iniciada', 'Fecha de entrega' => '02/12/2026',
                ]),
                $this->filaTarea('2-131-2026 · fila 4', 'Tarea', '2-131-2026', 'Medir la nave', [
                    'Propietario' => 'Laura', 'Estado' => 'No iniciada', 'Fecha de entrega' => '05/12/2026',
                ]),
                $this->filaTarea('Servicios generales · fila 3', 'Tarea', 'Sin obra', 'Service camioneta', [
                    'Propietario' => '(en el sector)', 'Sector' => 'Compras', 'Estado' => 'No iniciada', 'Fecha de entrega' => '04/12/2026',
                ]),
            ],
            'Personas' => [
                ['Como figura en la minuta' => 'Diego', 'Mail del usuario en el sistema' => 'diego@ejemplo.com'],
                ['Como figura en la minuta' => 'Laura', 'Mail del usuario en el sistema' => 'laura@ejemplo.com'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $resto
     * @return array<string, mixed>
     */
    protected function filaTarea(string $clave, string $queEs, string $obra, string $tarea, array $resto = []): array
    {
        return [
            'Clave' => $clave, 'Qué es' => $queEs, 'Obra' => $obra, 'Tarea' => $tarea, 'Con quién' => null, 'Prioridad' => null,
            'Propietario' => null, 'Sector' => null, 'Solicitante' => null, 'Estado' => null, 'Fecha de pedido' => null,
            'Fecha de entrega' => null, 'Inicio del período' => null, 'Fin del período' => null, 'Links' => null, 'Notas' => null,
            'Tipo de documento' => null, 'Fecha del documento' => null, ...$resto,
        ];
    }

    /** Las dos plantillas en la carpeta de la importación y las personas del caso. */
    protected function prepararCasoDeReferencia(): void
    {
        $this->carla();
        $this->laura();
        $this->diego();
        $this->sofia();
        $this->xlsx('obras.xlsx', $this->hojasDeObras(), ['Presupuestos' => self::columnasDePresupuestos()]);
        $this->xlsx('tareas.xlsx', $this->hojasDeTareas());
    }

    /**
     * @return list<string>
     */
    protected static function columnasDePresupuestos(): array
    {
        return ['Obra', 'N.º de presupuesto', 'Revisión', 'Descripción', 'Oferta técnica del adicional (link)', 'Documento de aprobación',
            'N.º de OC/OT', 'Link al documento de aprobación', 'Cómo se carga', 'MATERIALES', 'INSUMOS', 'SUBCONTRATOS', 'LOGISTICA',
            'AMORTIZACION', 'MANTENIMIENTO', 'OBRADORES/BAÑOS', 'SERVICIOS GENERALES', 'MAQUINAS/HERRAMIENTAS', 'VIATICOS', 'FIJOS',
            'HORAS LIQUIDADAS', 'Total', 'Monto del presupuesto'];
    }
}
