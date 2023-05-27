<?php

namespace Itinysun\Laraman\Traits;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

trait HasBaseState
{
    /**
     * 清理laravel基础状态,移植自octane
     * @return void
     */
    public function cleanBaseState(): void
    {
        $this->forgetScopedInstances();
        $this->flushMailer();
        $this->flushNotificationChannelManager();
        $this->flushDatabase();
        $this->flushLogContext();
        $this->flushArrayCache();
        $this->flushStrCache();
        $this->flushTranslatorCache();
        $this->prepareScoutForNextOperation();
        $this->prepareInertiaForNextOperation();
        $this->prepareLivewireForNextOperation();
        $this->PrepareSocialiteForNextOperation();
    }

    /**
     * laravel 自带ScopedInstances模式，但是它竟然不清理Facade缓存，坑爹，咱自己实现
     * 而且我不明白Facade为啥要独立维护一份实例缓存呢？
     * laravel don't clear facade instance cache ,so we clear ourselves
     * @return void
     */
    public function forgetScopedInstances(): void
    {
        foreach ($this->scopedInstances as $scoped) {
            unset($this->instances[$scoped]);
            Facade::clearResolvedInstance($scoped);
        }

    }
    /**
     * @return void
     */
    protected function flushNotificationChannelManager(): void
    {
        if (!$this->resolved(ChannelManager::class)) {
            return;
        }

        with($this->make(ChannelManager::class), function ($manager) {
            $manager->forgetDrivers();
        });
    }

    /**
     * @return void
     */
    protected function flushMailer(): void
    {
        if (!$this->resolved('mail.manager')) {
            return;
        }

        with($this->make('mail.manager'), function ($manager) {
            $manager->forgetMailers();
        });
    }

    /**
     * @return void
     */
    protected function flushArrayCache(): void
    {
        if (config('cache.stores.array')) {
            $this->make('cache')->store('array')->flush();
        }
    }

    /**
     * @return void
     */
    protected function flushStrCache(): void
    {
        Str::flushCache();
    }
    /**
     * @return void
     */
    protected function prepareInertiaForNextOperation(): void
    {
        if (!$this->resolved('\Inertia\ResponseFactory')) {
            return;
        }

        $factory = $this->make('\Inertia\ResponseFactory::class');

        if (method_exists($factory, 'flushShared')) {
            $factory->flushShared();
        }
    }

    /**
     * @return void
     */
    protected function prepareLivewireForNextOperation(): void
    {
        if (!$this->resolved('\Livewire\LivewireManager')) {
            return;
        }

        $manager = $this->make('\Livewire\LivewireManager');

        if (method_exists($manager, 'flushState')) {
            $manager->flushState();
        }
    }

    /**
     * @return void
     */
    protected function prepareScoutForNextOperation(): void
    {
        if (!$this->resolved('\Laravel\Scout\EngineManager')) {
            return;
        }

        $factory = $this->make('\Laravel\Scout\EngineManager');

        if (!method_exists($factory, 'forgetEngines')) {
            return;
        }

        $factory->forgetEngines();
    }

    /**
     * @return void
     */
    protected function PrepareSocialiteForNextOperation(): void
    {
        if (!$this->resolved('\Laravel\Socialite\Contracts\Factory')) {
            return;
        }

        $factory = $this->make('\Laravel\Socialite\Contracts\Factory');

        if (!method_exists($factory, 'forgetDrivers')) {
            return;
        }

        $factory->forgetDrivers();
    }
    /**
     * @return void
     */
    protected function flushDatabase(): void
    {
        if (!$this->resolved('db')) {
            return;
        }

        foreach ($this->make('db')->getConnections() as $connection) {
            if (
                method_exists($connection, 'resetTotalQueryDuration')
                && method_exists($connection, 'allowQueryDurationHandlersToRunAgain')
            ) {
                $connection->resetTotalQueryDuration();
                $connection->allowQueryDurationHandlersToRunAgain();
            }
            $connection->flushQueryLog();
            $connection->forgetRecordModificationState();
        }
    }

    /**
     * @return void
     */
    protected function flushLogContext(): void
    {
        if (!$this->resolved('log')) {
            return;
        }
        collect($this->make('log')->getChannels())
            ->map->getLogger()
            ->filter(function ($logger) {
                return $logger instanceof \Monolog\ResettableInterface;
            })->each->reset();

        if (method_exists($this['log'], 'flushSharedContext')) {
            $this['log']->flushSharedContext();
        }

        if (method_exists($this['log']->driver(), 'withoutContext')) {
            $this['log']->withoutContext();
        }
    }

    /**
     * @return void
     */
    protected function flushTranslatorCache(): void
    {
        if (!$this->resolved('translator')) {
            return;
        }

        $config = $this->make('config');

        $translator = $this->make('translator');

        if ($translator instanceof \Illuminate\Support\NamespacedItemResolver) {
            $translator->flushParsedKeys();
        }

        tap($translator, function ($translator) use ($config) {
            $translator->setLocale($config->get('app.locale'));
            $translator->setFallback($config->get('app.fallback_locale'));
        });

        /*
         * not very sure about what these mean
         * see Laravel\Octane\Listeners\FlushLocaleState;
        $provider = tap(new CarbonServiceProvider($event->app))->updateLocale();

        collect($event->sandbox->getProviders($provider))
            ->values()
            ->whenNotEmpty(fn ($providers) => $providers->first()->setAppGetter(fn () => $event->sandbox));
        */

    }
}
