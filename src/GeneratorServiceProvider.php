<?php

namespace Larahammer\Generator;

use Illuminate\Support\ServiceProvider;
use Larahammer\Generator\Commands\MakeCommand;

class GeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/larahammer.php',
            'larahammer'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/larahammer.php' => config_path('larahammer.php'),
            ], 'larahammer-config');

            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/larahammer'),
            ], 'larahammer-stubs');
        }
    }
}
