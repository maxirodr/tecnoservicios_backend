<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Obras\Domain\Models\Obra;

/**
 * F-17 / HU-014 (W-07, W-20): la ficha completa con presupuestos, documentos, contactos y lo que la
 * persona puede hacer (obras.md §7). Carga todo con eager loading (sin N+1).
 */
final readonly class FichaObraQuery
{
    public const RELACIONES = [
        'responsable', 'creadaPor', 'terminadaPor', 'principal', 'contactos',
        'presupuestos.adjuntos.subidoPor', 'presupuestos.creadoPor', 'documentos.adjuntos.subidoPor',
    ];

    public function __construct(
        private AccesoObra $acceso,
        private PedidosDeObra $pedidos,
        private AvanceGastoDeObra $avance,
    ) {}

    public function handle(User $user, Obra $obra): FichaObra
    {
        $obra->load(self::RELACIONES);
        $obra->setAttribute('adicionales_count', $obra->presupuestos->where('es_principal', false)->count());

        $puede = $this->acceso->resumen($user, $obra);
        $porcentaje = $puede['ver_rendicion'] ? ($this->avance->porcentajesGastados([$obra->id])[$obra->id] ?? null) : null;
        $pasada = $porcentaje === null ? null : ($this->avance->pasadas([$obra->id])[$obra->id] ?? false);

        return new FichaObra(
            new FilaObra($obra, $this->pedidos->contarAbiertosPorObra([$obra->id])[$obra->id] ?? null, $porcentaje, $pasada),
            $puede,
        );
    }
}
