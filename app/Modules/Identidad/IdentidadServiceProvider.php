<?php

declare(strict_types=1);

namespace App\Modules\Identidad;

use App\Modules\Identidad\Contracts\Jerarquia;
use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Contracts\PlazosPrioridad;
use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Http\Middleware\AsegurarUsuarioActivo;
use App\Modules\Identidad\Http\Middleware\VerificarPermiso;
use App\Modules\Identidad\Infrastructure\Console\CrearAdministradorCommand;
use App\Modules\Identidad\Infrastructure\JerarquiaPorJefe;
use App\Modules\Identidad\Infrastructure\MatrizPermisos;
use App\Modules\Identidad\Infrastructure\PlazosVigentes;
use App\Modules\Identidad\Infrastructure\SinObrasDelResponsable;
use App\Shared\Http\ApiResponse;
use App\Shared\Providers\ModuleServiceProvider;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

final class IdentidadServiceProvider extends ModuleServiceProvider
{
    public const LIMITE_PEDIR_CODIGO = 'identidad-pedir-codigo';

    public const LIMITE_INGRESO = 'identidad-ingreso';

    public const DEMASIADOS_CODIGOS = 'Ya pediste varios códigos. Probá de nuevo en unos minutos.';

    public const DEMASIADOS_INTENTOS = 'Demasiados intentos. Probá de nuevo en un minuto.';

    /** RN-IDE-06: pedir código, 3 cada 10 minutos por mail y 20 por hora por IP. */
    private const CODIGOS_POR_MAIL = 3;

    private const MINUTOS_POR_MAIL = 10;

    private const CODIGOS_POR_IP_POR_HORA = 20;

    /** RN-IDE-06: entrar, 10 intentos por minuto por mail y 30 por IP (el código ya se invalida al quinto fallo). */
    private const INGRESOS_POR_MAIL_POR_MINUTO = 10;

    private const INGRESOS_POR_IP_POR_MINUTO = 30;

    /** @var array<class-string, class-string> */
    public array $bindings = [
        Jerarquia::class => JerarquiaPorJefe::class,
        PlazosPrioridad::class => PlazosVigentes::class,
    ];

    public function register(): void
    {
        // TL-05: una instancia por pedido (o por job de la cola), que recuerda las celdas leídas.
        $this->app->scoped(MatrizPermisos::class);
        $this->app->scoped(Permisos::class, static fn (Application $app): Permisos => $app->make(MatrizPermisos::class));
        // Obras registra la suya al existir (su provider carga después).
        $this->app->bindIf(ObrasDelResponsable::class, SinObrasDelResponsable::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }

    public function boot(): void
    {
        parent::boot();

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permiso', VerificarPermiso::class);
        $router->pushMiddlewareToGroup('api', AsegurarUsuarioActivo::class);

        $this->limites();
        $this->olvidarPermisosAlCambiar();

        if ($this->app->runningInConsole()) {
            $this->commands([CrearAdministradorCommand::class]);
        }
    }

    /** La matriz recordada vale solo dentro del pedido y hasta que se toque una celda o un sector. */
    private function olvidarPermisosAlCambiar(): void
    {
        $olvidar = fn () => $this->app->make(MatrizPermisos::class)->olvidar();

        Event::listen(RequestHandled::class, $olvidar);
        Permiso::saved($olvidar);
        Permiso::deleted($olvidar);
        Sector::saved($olvidar);
    }

    private function limites(): void
    {
        $mail = static fn (Request $request): string => MailNormalizado::de($request->string('email')->toString());

        RateLimiter::for(self::LIMITE_PEDIR_CODIGO, fn (Request $request): array => [
            Limit::perMinutes(self::MINUTOS_POR_MAIL, self::CODIGOS_POR_MAIL)->by('mail|'.$mail($request))
                ->response(self::demasiados(self::DEMASIADOS_CODIGOS)),
            Limit::perHour(self::CODIGOS_POR_IP_POR_HORA)->by('ip|'.$request->ip())
                ->response(self::demasiados(self::DEMASIADOS_CODIGOS)),
        ]);

        RateLimiter::for(self::LIMITE_INGRESO, fn (Request $request): array => [
            Limit::perMinute(self::INGRESOS_POR_MAIL_POR_MINUTO)->by('mail|'.$mail($request))
                ->response(self::demasiados(self::DEMASIADOS_INTENTOS)),
            Limit::perMinute(self::INGRESOS_POR_IP_POR_MINUTO)->by('ip|'.$request->ip())
                ->response(self::demasiados(self::DEMASIADOS_INTENTOS)),
        ]);
    }

    /**
     * @return Closure(Request, array<string, string>): JsonResponse
     */
    private static function demasiados(string $mensaje): Closure
    {
        return static fn (Request $request, array $headers): JsonResponse => ApiResponse::error($mensaje, 429, null, $headers);
    }
}
