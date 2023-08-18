<?php

namespace Itinysun\Laraman;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Itinysun\Laraman\Command\ConfigProxy;
use Itinysun\Laraman\Events\RequestReceived;
use Itinysun\Laraman\Listeners\OwlAdminExtensionChanged;

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

            /*
             * 框架兼容处理
             */
            if(class_exists(\Slowlyo\OwlAdmin\Admin::class)){
                Event::listen(\Slowlyo\OwlAdmin\Events\ExtensionChanged::class, OwlAdminExtensionChanged::class);
            }
            if(class_exists(\Dcat\Admin\Admin::class)){
                Event::listen(RequestReceived::class,\Dcat\Admin\Octane\Listeners\FlushAdminState::class);
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
            __DIR__.'/../config/schedule.php' => config_path('laraman/schedule.php'),
            __DIR__ . '/../config/starter.php' =>base_path('laraman')
        ],'laraman.install');
    }
}
