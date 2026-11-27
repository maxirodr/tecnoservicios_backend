<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Modo;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Lo que comparte una corrida mientras corre: quién figura (`actorId`, RN-IMP-10), el informe y el registro de
 * filas. En la carga confirmada cada fila se registra **dentro** de la transacción de su obra o su tarea (si el
 * proceso se corta, lo guardado ya tiene su fila, RN-IMP-07, 08); en el ensayo se registra después del rollback.
 */
final class Contexto
{
    /** @var list<FilaDeCorrida> */
    private array $pendientes = [];

    /** @var list<string> */
    public array $consola = [];

    public bool $conErrores = false;

    public function __construct(
        public readonly int $corridaId,
        public readonly int $actorId,
        public readonly Modo $modo,
        public readonly Informe $informe,
        private readonly RegistroDeCorridas $corridas,
        private readonly LoggerInterface $log,
    ) {}

    /**
     * @param  list<FilaDeCorrida>  $filas
     */
    public function registrar(array $filas): void
    {
        if ($this->modo === Modo::Confirmada) {
            $this->corridas->guardarFilas($this->corridaId, $filas);
        } else {
            array_push($this->pendientes, ...$filas);
        }
    }

    /** Las filas del ensayo, ya con la transacción deshecha. */
    public function registrarPendientes(): void
    {
        $this->corridas->guardarFilas($this->corridaId, $this->pendientes);
        $this->pendientes = [];
    }

    /**
     * RN-IMP-07 y RN-IMP-13: el error inesperado queda con la corrida, la hoja, la fila y el tipo de error, **sin el
     * valor de ninguna celda** (ni el mensaje, que puede tenerlos). El detalle va solo al informe.
     */
    public function errorInesperado(string $hoja, int $fila, Throwable $error, string $consola): void
    {
        $this->conErrores = true;
        $this->consola[] = $consola;
        $this->log->error('Carga inicial: error inesperado', [
            'corrida' => $this->corridaId,
            'hoja' => $hoja,
            'fila' => $fila,
            'tipo' => $error::class,
        ]);
    }

    public function confirmada(): bool
    {
        return $this->modo === Modo::Confirmada;
    }
}
