<?php

namespace Itinysun\Laraman;

use Illuminate\Support\ServiceProvider;

class LaramanServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();

        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
       $this->mergeConfigFrom(__DIR__.'/../config/server.php', 'laraman.server');
        $this->mergeConfigFrom(__DIR__.'/../config/static.php', 'laraman.static');
    }


    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/server.php' => config_path('laraman/server.php'),
            __DIR__.'/../config/static.php' => config_path('laraman/static.php'),
            __DIR__ . '/../config/starter.php' =>base_path('laraman')
        ],'laraman.install');
        $this->commands([
            Laraman::class
        ]);
    }
}
