<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Console\SupervisorSyncCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        // N+1 y atributos inexistentes explotan en dev/tests; en prod no se corta al usuario.
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->runningInConsole()) {
            $this->commands([SupervisorSyncCommand::class]);
        }
    }
}
