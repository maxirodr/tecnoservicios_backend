<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

use App\Shared\CargaInicial\CargaRechazada;

/**
 * La carga inicial de obras (`importacion.md` §11.4, RN-IMP-18…22, 38): Obras la hace con sus casos de uso y sus
 * mismas validaciones (alta de obra, estado, montos, monto de la obra, documentos). El "creó" es quien aprobó.
 * Lo que el caso de uso rechaza llega como `CargaRechazada`, con los textos de la pantalla.
 */
interface CargaInicialDeObras
{
    /**
     * N.º de presupuesto normalizado, con la validación tolerante de la pantalla (RN-OBR-11): "2-131-2026 A1".
     *
     * @throws CargaRechazada
     */
    public function numero(string $escrito): string;

    /**
     * Revisión normalizada (RN-OBR-14): "REV01".
     *
     * @throws CargaRechazada
     */
    public function revision(string $escrita): string;

    /** La obra cuyo presupuesto principal tiene ese N.º (normalizado), si ya está en el sistema. */
    public function existe(string $numeroPrincipal): ?int;

    /** El trabajo de la obra que ya tiene ese N.º en cualquiera de sus presupuestos (RN-OBR-12). */
    public function obraConNumero(string $numero): ?string;

    /** `confirmada` · `en_ejecucion` · `terminada`, o null si la obra no existe. */
    public function estado(int $obraId): ?string;

    /**
     * Alta de la obra con sus presupuestos, contactos, oferta técnica (link) y montos; nace Confirmada y, si
     * corresponde, pasa a En ejecución en la misma transacción (RN-IMP-21). Sin avisos.
     *
     * @throws CargaRechazada
     */
    public function crear(int $actorId, ObraInicial $obra): int;

    /**
     * Certificado u otro documento de la obra con sus links (RN-IMP-38).
     *
     * @throws CargaRechazada
     */
    public function agregarDocumento(int $actorId, int $obraId, DocumentoInicial $documento): int;
}
