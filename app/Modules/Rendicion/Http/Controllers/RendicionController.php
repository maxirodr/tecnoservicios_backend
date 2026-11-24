<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Controllers;

use App\Modules\Rendicion\Application\Acceso\AccesoRendicion;
use App\Modules\Rendicion\Application\Rendicion\DetalleDeRubroQuery;
use App\Modules\Rendicion\Application\Rendicion\PorProveedorQuery;
use App\Modules\Rendicion\Application\Rendicion\RendicionDeObraQuery;
use App\Modules\Rendicion\Application\Rendicion\RendicionesEnCursoQuery;
use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Http\Requests\DetalleDeRubroRequest;
use App\Modules\Rendicion\Http\Requests\PorProveedorRequest;
use App\Modules\Rendicion\Http\Resources\LecturasResource;
use App\Modules\Rendicion\Http\Resources\RendicionResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * F-49…F-54 (W-11, W-44, W-36, W-37, W-12): todo se calcula, nada se carga acá (RN-REN-57). `rendicion` ≥ ver
 * con su alcance; fuera de alcance, 404 (RN-REN-55).
 */
final class RendicionController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __construct(private readonly AccesoRendicion $acceso) {}

    public function show(Request $request, int $obra, RendicionDeObraQuery $rendicion, CatalogoDeRubros $catalogo): JsonResponse
    {
        $user = $this->usuario($request);
        $nombres = [];
        foreach ($catalogo->imputables() as $rubro) {
            $nombres[$rubro->clave] = $rubro->nombre;
        }

        return $this->ok(RendicionResource::deObra($rendicion->handle($user, $this->acceso->obraDeRendicion($user, $obra)), $nombres));
    }

    public function rubro(DetalleDeRubroRequest $request, int $obra, string $clave, DetalleDeRubroQuery $detalle): JsonResponse
    {
        $resumen = $this->acceso->obraDeRendicion($this->usuario($request), $obra);

        return $this->ok(LecturasResource::detalle($detalle->handle($resumen, $clave, $request->orden(), $request->dir())));
    }

    public function porProveedor(PorProveedorRequest $request, int $obra, PorProveedorQuery $porProveedor): JsonResponse
    {
        $resumen = $this->acceso->obraDeRendicion($this->usuario($request), $obra);
        $filtros = ['rubro' => $request->rubro(), 'desde' => $request->desde(), 'hasta' => $request->hasta()];

        return $this->ok(LecturasResource::porProveedor($porProveedor->handle($resumen, ...$filtros), $filtros));
    }

    public function enCurso(Request $request, RendicionesEnCursoQuery $enCurso): JsonResponse
    {
        return $this->ok(LecturasResource::enCurso($enCurso->handle($this->acceso->obrasDeRendicion($this->usuario($request)))));
    }
}
