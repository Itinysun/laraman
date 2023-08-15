<?php

namespace Itinysun\Laraman;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Itinysun\Laraman\Command\ConfigProxy;

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
            if($this->app->resolved('admin.extend')){
                Event::listen(\Slowlyo\OwlAdmin\Events\ExtensionChanged::class,\Itinysun\Laraman\Listeners\OwlAdminExtensionChanged::class);
            }
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->commands([ConfigProxy::class]);
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
            __DIR__.'/../config/web.php' => config_path('laraman/web.php'),
            __DIR__.'/../config/monitor.php' => config_path('laraman/monitor.php'),
            __DIR__.'/../config/server.php' => config_path('laraman/server.php'),
            __DIR__ . '/../config/starter.php' =>base_path('laraman')
        ],'laraman.install');
    }
}
