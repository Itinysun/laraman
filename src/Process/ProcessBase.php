<?php

namespace Itinysun\Laraman\Process;

use App\Exceptions\Handler;
use App\Http\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Str;
use Itinysun\Laraman\Server\LaramanApp;
use Itinysun\Laraman\Server\LaramanKernel;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 *
 */
class ProcessBase
{

    protected Application $app;

    protected Kernel $kernel;
    /**
     * @var Worker
     */
    protected Worker $worker;

    protected ExceptionHandler $exceptionHandler;

    protected bool $cleanWebStateAfterMessage = true;

    protected bool $cleanBaseStateAfterMessage = true;

    protected array $params = [];

    public function __construct($params = [])
    {
        $this->params = $params;
    }

    /**
     * @throws \Throwable
     */
    public static function buildWorker($configName, $processName = null): Worker
    {
        $config = config("laraman.process.$processName.workman");
        if (!isset($config['count']) || $config['count'] === 0) {
            $config['count'] = cpu_count() * 4;
        }
        if (!$processName)
            $processName = $configName;


        $worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);
        $propertyMap = [
            'count',
            'user',
            'group',
            'reloadable',
            'reusePort',
            'transport',
            'protocol',
        ];

        $worker->name = $processName;
        foreach ($propertyMap as $property) {
            if (isset($config[$property])) {
                $worker->$property = $config[$property];
            }
        }




        return $worker;
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-worker-start.html
     * @param Worker $worker
     * @return void
     */
    protected function onWorkerStart(Worker $worker): void
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-connect.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onConnect(TcpConnection $connection)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-worker-reload.html
     * @param Worker $worker
     * @return void
     */
    protected function onWorkerReload(Worker $worker)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-message.html
     * @param TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request $workmanRequest
     * @return void
     */
    protected function onHttpMessage(TcpConnection $connection, \Workerman\Protocols\Http\Request $workmanRequest)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-message.html
     * @param TcpConnection $connection
     * @param string $data
     * @return void
     */
    protected function onTextMessage(TcpConnection $connection, string $data)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-close.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onClose(TcpConnection $connection)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-buffer-full.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onBufferFull(TcpConnection $connection)
    {
    }

    /**
     * @link  https://www.workerman.net/doc/workerman/worker/on-buffer-drain.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onBufferDrain(TcpConnection $connection)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-error.html
     * @param TcpConnection $connection
     * @param $code
     * @param $msg
     * @return void
     */
    protected function onError(TcpConnection $connection, $code, $msg)
    {
    }


    public function _onWorkerStart(Worker $worker): void
    {
        $this->worker = $worker;
        $this->app = new LaramanApp(base_path());
        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            LaramanKernel::class
        );
        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );
        $this->app->singleton(
            ExceptionHandler::class,
            Handler::class
        );
        $this->kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->exceptionHandler = $this->app->make(ExceptionHandler::class);
        $this->fixUploadedFile();
        $this->onWorkerStart($worker);
    }

    public function _onWorkerReload(Worker $worker): void
    {
        $this->worker = $worker;
        $this->onWorkerReload($worker);
    }

    public function _onConnect(TcpConnection $connection): void
    {
        $this->onConnect($connection);
    }

    public function _onMessage(TcpConnection $connection, $data): void
    {
        $isWebRequest = $data instanceof Request;
        if ($this->worker->protocol != null && $this->worker->protocol == 'Workerman\Protocols\Http') {
            $this->onHttpMessage($connection, $data);
        } else {
            $this->onTextMessage($connection, $data);
        }
        if ($this->cleanBaseStateAfterMessage)
            $this->cleanBaseState();
        if ($this->cleanWebStateAfterMessage) {
            $this->cleanWebState();
            if ($isWebRequest)
                $this->flushUploadedFiles($data);
        }
    }

    public function _onClose(TcpConnection $connection): void
    {
        $this->onClose($connection);
    }

    public function _onBufferFull(TcpConnection $connection): void
    {
        $this->onBufferFull($connection);
    }

    public function _onBufferDrain(TcpConnection $connection): void
    {
        $this->onBufferDrain($connection);
    }

    protected function cleanBaseState(): void
    {
        $this->restScope();
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

    protected function cleanWebState(): void
    {
        $this->flushQueuedCookie();
        $this->flushSessionState();
        $this->flushAuthenticationState();
    }

    protected function restScope(): void
    {
        if (method_exists($this->app, 'resetScope')) {
            $this->app->resetScope();
        }

        if (method_exists($this->app, 'forgetScopedInstances')) {
            $this->app->forgetScopedInstances();
        }
    }

    protected function flushNotificationChannelManager(): void
    {
        if (!$this->app->resolved(ChannelManager::class)) {
            return;
        }

        with($this->app->make(ChannelManager::class), function ($manager) {
            $manager->forgetDrivers();
        });
    }

    protected function flushMailer(): void
    {
        if (!$this->app->resolved('mail.manager')) {
            return;
        }

        with($this->app->make('mail.manager'), function ($manager) {
            $manager->forgetMailers();
        });
    }

    protected function flushArrayCache(): void
    {
        if (config('cache.stores.array')) {
            $this->app->make('cache')->store('array')->flush();
        }
    }

    protected function flushStrCache(): void
    {
        Str::flushCache();
    }

    protected function fixUploadedFile(): void
    {
        $fixesDir = dirname(__DIR__) . '../../fixes';
        if (!function_exists('\\Symfony\\Component\\HttpFoundation\\File\\is_uploaded_file')) {
            require $fixesDir . '/fix-symfony-file-validation.php';
        }
        if (!function_exists('\\Symfony\\Component\\HttpFoundation\\File\\move_uploaded_file')) {
            require $fixesDir . '/fix-symfony-file-moving.php';
        }
    }

    protected function prepareInertiaForNextOperation(): void
    {
        if (!$this->app->resolved('\Inertia\ResponseFactory')) {
            return;
        }

        $factory = $this->app->make('\Inertia\ResponseFactory::class');

        if (method_exists($factory, 'flushShared')) {
            $factory->flushShared();
        }
    }

    protected function prepareLivewireForNextOperation(): void
    {
        if (!$this->app->resolved('\Livewire\LivewireManager')) {
            return;
        }

        $manager = $this->app->make('\Livewire\LivewireManager');

        if (method_exists($manager, 'flushState')) {
            $manager->flushState();
        }
    }

    protected function prepareScoutForNextOperation(): void
    {
        if (!$this->app->resolved('\Laravel\Scout\EngineManager')) {
            return;
        }

        $factory = $this->app->make('\Laravel\Scout\EngineManager');

        if (!method_exists($factory, 'forgetEngines')) {
            return;
        }

        $factory->forgetEngines();
    }

    protected function PrepareSocialiteForNextOperation(): void
    {
        if (!$this->app->resolved('\Laravel\Socialite\Contracts\Factory')) {
            return;
        }

        $factory = $this->app->make('Laravel\Socialite\Contracts\Factory');

        if (!method_exists($factory, 'forgetDrivers')) {
            return;
        }

        $factory->forgetDrivers();
    }


    protected function flushAuthenticationState(): void
    {
        if ($this->app->resolved('auth.driver')) {
            $this->app->forgetInstance('auth.driver');
        }

        if ($this->app->resolved('auth')) {
            with($this->app->make('auth'), function ($auth) {
                $auth->forgetGuards();
            });
        }
    }

    protected function flushSessionState(): void
    {
        if (!$this->app->resolved('session')) {
            return;
        }

        $driver = $this->app->make('session')->driver();

        $driver->flush();
        $driver->regenerate();
    }

    protected function flushQueuedCookie(): void
    {
        if (!$this->app->resolved('cookie')) {
            return;
        }
        $this->app->make('cookie')->flushQueuedCookies();
    }

    protected function flushDatabase(): void
    {
        if (!$this->app->resolved('db')) {
            return;
        }

        foreach ($this->app->make('db')->getConnections() as $connection) {
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

    protected function flushLogContext(): void
    {
        if (!$this->app->resolved('log')) {
            return;
        }
        collect($this->app->make('log')->getChannels())
            ->map->getLogger()
            ->filter(function ($logger) {
                return $logger instanceof \Monolog\ResettableInterface;
            })->each->reset();

        if (method_exists($this->app['log'], 'flushSharedContext')) {
            $this->app['log']->flushSharedContext();
        }

        if (method_exists($this->app['log']->driver(), 'withoutContext')) {
            $this->app['log']->withoutContext();
        }
    }

    protected function flushTranslatorCache(): void
    {
        if (!$this->app->resolved('translator')) {
            return;
        }

        $config = $this->app->make('config');

        $translator = $this->app->make('translator');

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

    protected function flushUploadedFiles(Request $request): void
    {
        foreach ($request->files->all() as $file) {
            if (!$file instanceof \SplFileInfo ||
                !is_string($path = $file->getRealPath())) {
                continue;
            }

            clearstatcache(true, $path);

            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
