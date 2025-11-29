<?php

namespace LaravelArchitect;

use Illuminate\Support\ServiceProvider;

class LaravelArchitectServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api_scaffolding.php' => config_path('api_scaffolding.php'),
            ], 'api-scaffolding-config');

            $this->commands([
                Console\Commands\MakeApiBundleCommand::class,
                Console\Commands\MakeApiModelCommand::class,
                Console\Commands\MakeApiMigrationCommand::class,
                Console\Commands\MakeApiControllerCommand::class,
                Console\Commands\MakeApiRequestCommand::class,
                Console\Commands\MakeApiServiceCommand::class,
                Console\Commands\MakeApiRepositoryCommand::class,
                Console\Commands\MakeApiResourceCommand::class,
                Console\Commands\MakeApiFactoryCommand::class,
                Console\Commands\MakeApiTestCommand::class,
                Console\Commands\MakeApiRoutesCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/api_scaffolding.php',
            'api_scaffolding'
        );
    }
}
