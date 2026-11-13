<?php

declare(strict_types=1);

namespace App\Modules\Identidad;

use App\Modules\Identidad\Contracts\Jerarquia;
use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Contracts\PlazosPrioridad;
use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Http\Middleware\AsegurarUsuarioActivo;
use App\Modules\Identidad\Http\Middleware\VerificarPermiso;
use App\Modules\Identidad\Infrastructure\Console\CrearAdministradorCommand;
use App\Modules\Identidad\Infrastructure\JerarquiaPorJefe;
use App\Modules\Identidad\Infrastructure\MatrizPermisos;
use App\Modules\Identidad\Infrastructure\PlazosVigentes;
use App\Modules\Identidad\Infrastructure\SinObrasDelResponsable;
use App\Shared\Http\ApiResponse;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

final class IdentidadServiceProvider extends ModuleServiceProvider
{
    public const LIMITE_INGRESO = 'identidad-ingreso';

    public const LIMITE_RECUPERACION = 'identidad-recuperacion';

    public const LIMITE_BIENVENIDA = 'identidad-bienvenida';

    /** ADR-009: contraseña de al menos 10 caracteres. */
    private const LARGO_MINIMO_CONTRASENA = 10;

    private const INGRESOS_POR_MINUTO = 5;

    private const RECUPERACIONES_POR_HORA = 3;

    private const BIENVENIDAS_POR_HORA = 3;

    /** @var array<class-string, class-string> */
    public array $bindings = [
        Permisos::class => MatrizPermisos::class,
        Jerarquia::class => JerarquiaPorJefe::class,
        PlazosPrioridad::class => PlazosVigentes::class,
    ];

    public function register(): void
    {
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

        Password::defaults(static fn (): Password => Password::min(self::LARGO_MINIMO_CONTRASENA));

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permiso', VerificarPermiso::class);
        $router->pushMiddlewareToGroup('api', AsegurarUsuarioActivo::class);

        $this->limites();

        if ($this->app->runningInConsole()) {
            $this->commands([CrearAdministradorCommand::class]);
        }
    }

    private function limites(): void
    {
        // RN-IDE-05: 5 intentos por minuto por mail + IP.
        RateLimiter::for(self::LIMITE_INGRESO, fn (Request $request): Limit => Limit::perMinute(self::INGRESOS_POR_MINUTO)
            ->by(MailNormalizado::de($request->string('email')->toString()).'|'.$request->ip())
            ->response(fn (Request $request, array $headers) => ApiResponse::error('Demasiados intentos. Probá de nuevo en un minuto.', 429, null, $headers)));

        // RN-IDE-06: 3 pedidos por hora por mail.
        RateLimiter::for(self::LIMITE_RECUPERACION, fn (Request $request): Limit => Limit::perHour(self::RECUPERACIONES_POR_HORA)
            ->by(MailNormalizado::de($request->string('email')->toString()))
            ->response(fn (Request $request, array $headers) => ApiResponse::error('Ya pediste varios mails. Probá de nuevo en una hora.', 429, null, $headers)));

        // identidad.md §10: reenviar bienvenida, 3 por hora por usuario.
        RateLimiter::for(self::LIMITE_BIENVENIDA, fn (Request $request): Limit => Limit::perHour(self::BIENVENIDAS_POR_HORA)
            ->by('bienvenida|'.(string) $request->route('usuario'))
            ->response(fn (Request $request, array $headers) => ApiResponse::error('Ya le mandamos varios mails. Probá de nuevo en una hora.', 429, null, $headers)));
    }
}
