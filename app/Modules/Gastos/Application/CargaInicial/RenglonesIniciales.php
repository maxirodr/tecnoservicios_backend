<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\CargaInicial;

use App\Modules\Gastos\Application\Carga\RenglonAGuardar;
use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Contracts\RenglonInicial;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Shared\CargaInicial\CargaRechazada;
use App\Shared\Domain\Importe;

/**
 * Las reglas de los renglones de la carga inicial (`gastos.md` §4.3, filas "Carga inicial"): la fecha de corte no es
 * posterior a hoy; gastado = cantidad 1, cualquiera de los 13 rubros, sin material, importe ≥ 0; retirado =
 * MATERIALES, un material de la obra, cantidad ≥ 0 y precio 0 (RN-IMP-23, 29). El total se calcula acá (ADR-008).
 */
final readonly class RenglonesIniciales
{
    private const RUBRO_RETIRADO = 'materiales';

    private const DECIMAL = '/^\d+(\.\d+)?$/';

    public function __construct(
        private Rubros $rubros,
        private MaterialesDeObra $materiales,
    ) {}

    /**
     * @param  list<RenglonInicial>  $renglones
     * @return list<RenglonAGuardar>
     *
     * @throws CargaRechazada
     */
    public function handle(int $obraId, string $fechaCorte, bool $retirado, array $renglones): array
    {
        if ($renglones === []) {
            throw CargaRechazada::por('La carga inicial necesita al menos un renglón.');
        }

        if ($fechaCorte > FechaEmpresa::hoy()) {
            throw CargaRechazada::por('La fecha de corte no puede ser posterior a hoy.');
        }

        return array_map(fn (int $i): RenglonAGuardar => $this->renglon($obraId, $fechaCorte, $retirado, $i, $renglones[$i]), array_keys($renglones));
    }

    private function renglon(int $obraId, string $fecha, bool $retirado, int $posicion, RenglonInicial $r): RenglonAGuardar
    {
        $rubroId = $this->rubros->idDe($r->rubroClave) ?? throw CargaRechazada::por("El rubro '{$r->rubroClave}' no es uno de los 13.");
        self::exigirDecimal($r->cantidad, TotalDeRenglon::ESCALA_CANTIDAD);
        self::exigirDecimal($r->precio, TotalDeRenglon::ESCALA_IMPORTE);
        $cantidad = bcadd($r->cantidad, '0', TotalDeRenglon::ESCALA_CANTIDAD);
        $precio = bcadd($r->precio, '0', TotalDeRenglon::ESCALA_IMPORTE);

        if ($retirado) {
            $this->exigirRetirado($obraId, $r, $precio);
        } elseif ($r->materialId !== null || bccomp($cantidad, '1', TotalDeRenglon::ESCALA_CANTIDAD) !== 0) {
            throw CargaRechazada::por('El gastado va con cantidad 1 y sin material.');
        }

        return new RenglonAGuardar(
            $posicion,
            null,
            $fecha,
            $r->concepto,
            $cantidad,
            $precio,
            TotalDeRenglon::calcular($cantidad, $precio),
            $obraId,
            $rubroId,
            $r->materialId,
            null,
            null,
        );
    }

    /** DU-R09: el retirado no suma plata (el importe ya está en el gastado por rubro). */
    private function exigirRetirado(int $obraId, RenglonInicial $r, string $precio): void
    {
        if ($r->rubroClave !== self::RUBRO_RETIRADO || bccomp($precio, '0', TotalDeRenglon::ESCALA_IMPORTE) !== 0) {
            throw CargaRechazada::por('El retirado va en MATERIALES con precio 0.');
        }

        if ($r->materialId === null || ! $this->materiales->perteneceAObra($r->materialId, $obraId)) {
            throw CargaRechazada::por('El retirado necesita un material de la lista de la obra.');
        }
    }

    /**
     * @phpstan-assert numeric-string $valor
     */
    private static function exigirDecimal(string $valor, int $escala): void
    {
        if (preg_match(self::DECIMAL, $valor) !== 1 || Importe::decimales($valor) > $escala) {
            throw CargaRechazada::por($valor === '' ? 'Falta un importe.' : 'Un importe o una cantidad no es válido.');
        }
    }
}
