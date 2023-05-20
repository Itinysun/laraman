<?php

namespace Itinysun\Laraman\server;

use App\Exceptions\Handler;
use App\Http\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Itinysun\Laraman\Http\Response;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkmanRequest;
use Workerman\Worker;

class ApiServer
{

    protected static ?Worker $worker = null;

    protected Application $app;

    protected Kernel $kernel;


    /**
     * OnMessage.
     * @param TcpConnection|mixed $connection
     * @return null
     */
    public function onMessage(mixed $connection,WorkmanRequest $workmanRequest)
    {
        $request = Request::createFromBase(\Itinysun\Laraman\Http\Request::createFromWorkmanRequest($workmanRequest));

        if (str_contains($request->path(), '.')) {
            $resp = StaticFileServer::resolvePath($request->path(),$request);
            if(false!==$resp){
                $this->send($connection,$resp,$request);
                return null;
            }
        }


        try {

            $this->refreshTelescope($request);

            $response = $this->getResponse($request);
            $this->send($connection, $response, $request);
        } catch (Throwable $e) {
            $resp = json_encode([
                'code' => 500,
                'data' => ['code' => $e->getCode(), 'msg' => $e->getMessage()],
                'msg' => 'unhandled exception from server',
                'request_id' => ''
            ]);
            $this->send($connection, new Response(200,[],$resp), $request);
        }
        $this->prepareNextRequest($request);
        return null;
    }

    protected function prepareNextRequest(Request $request): void
    {
        if (method_exists($this->app, 'resetScope')) {
            $this->app->resetScope();
        }

        if (method_exists($this->app, 'forgetScopedInstances')) {
            $this->app->forgetScopedInstances();
        }
        $this->app->forgetScopedInstances();
        $this->flushSessionState();
        $this->flushQueuedCookie();
        Str::flushCache();
        $this->flushDatabase();

        if (config('cache.stores.array')) {
            $this->app->make('cache')->store('array')->flush();
        }
        $this->flushAuthenticationState();
        $this->flushLogContext();
        $this->flushTranslatorCache();
        $this->flushMailer();
        $this->flushNotificationChannelManager();
        $this->prepareInertiaForNextOperation();
        $this->prepareLivewireForNextOperation();
        $this->prepareScoutForNextOperation();
        $this->PrepareSocialiteForNextOperation();
        $this->flushUploadedFiles($request);
    }

    /**
     * OnWorkerStart.
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        static::$worker = $worker;
        $this->app = new LaramanApp(LARAMAN_PATH);
        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            LaramanKernel::class
        );
        $this->app->singleton(
            ExceptionHandler::class,
            Handler::class
        );
        $this->kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->fixUploadedFile();
        StaticFileServer::$public_path = public_path();

    }

    /**
     * Send.
     * @param TcpConnection|mixed $connection
     * @param Response $response
     * @param Request $request
     * @return void
     */
    protected function send(mixed $connection, Response $response, Request $request): void
    {
        $keepAlive = $request->header('connection');

        if (($keepAlive === null && $request->getProtocolVersion() === '1.1')
            || $keepAlive === 'keep-alive' || $keepAlive === 'Keep-Alive'
        ) {
            $connection->send($response);
            return;
        }
        $connection->close($response);
    }

    public function getResponse(Request $request): Response
    {

        $response = $this->kernel->handle(
            $request
        );

        $this->kernel->terminate($request, $response);

        return new Response($response->getStatusCode(),$response->headers->all(),$response->content());
    }

    protected function flushNotificationChannelManager(): void
    {
        if (! $this->app->resolved(ChannelManager::class)) {
            return;
        }

        with($this->app->make(ChannelManager::class), function ($manager) {
            $manager->forgetDrivers();
        });
    }
    protected function flushMailer(): void
    {
        if (! $this->app->resolved('mail.manager')) {
            return;
        }

        with($this->app->make('mail.manager'), function ($manager) {
            $manager->forgetMailers();
        });
    }

    protected function fixUploadedFile(): void
    {
        $fixesDir = dirname(__DIR__).'/fixes';
        if (! function_exists('\\Symfony\\Component\\HttpFoundation\\File\\is_uploaded_file')) {
            require $fixesDir.'/fix-symfony-file-validation.php';
        }
        if (! function_exists('\\Symfony\\Component\\HttpFoundation\\File\\move_uploaded_file')) {
            require $fixesDir.'/fix-symfony-file-moving.php';
        }
    }

    protected function prepareInertiaForNextOperation(): void
    {
        if (! $this->app->resolved('\Inertia\ResponseFactory')) {
            return;
        }

        $factory = $this->app->make('\Inertia\ResponseFactory::class');

        if (method_exists($factory, 'flushShared')) {
            $factory->flushShared();
        }
    }
    protected function prepareLivewireForNextOperation(): void
    {
        if (! $this->app->resolved('\Livewire\LivewireManager')) {
            return;
        }

        $manager = $this->app->make('\Livewire\LivewireManager');

        if (method_exists($manager, 'flushState')) {
            $manager->flushState();
        }
    }

    protected function prepareScoutForNextOperation(): void
    {
        if (! $this->app->resolved('\Laravel\Scout\EngineManager')) {
            return;
        }

        $factory = $this->app->make('\Laravel\Scout\EngineManager');

        if (! method_exists($factory, 'forgetEngines')) {
            return;
        }

        $factory->forgetEngines();
    }

    protected function PrepareSocialiteForNextOperation(): void
    {
        if (! $this->app->resolved('\Laravel\Socialite\Contracts\Factory')) {
            return;
        }

        $factory = $this->app->make('Laravel\Socialite\Contracts\Factory');

        if (! method_exists($factory, 'forgetDrivers')) {
            return;
        }

        $factory->forgetDrivers();
    }

    protected function refreshTelescope($request): void
    {
        if (! config('telescope.enabled')) {
            return;
        }
        if(static::requestIsToApprovedDomain($request) &&
            static::requestIsToApprovedUri($request)){
            \Laravel\Telescope\Telescope::startRecording($loadMonitoredTags = false);
        }else{
            \Laravel\Telescope\Telescope::stopRecording();
        }
    }
    protected static function requestIsToApprovedDomain($request): bool
    {
        return is_null(config('telescope.domain')) ||
            config('telescope.domain') !== $request->getHost();
    }

    /**
     * Determine if the request is to an approved URI.
     *
     * @param Request $request
     * @return bool
     */
    protected static function requestIsToApprovedUri(Request $request): bool
    {
        Log::debug('telescope check url');

        if (! empty($only = config('telescope.only_paths', []))) {

            return $request->is($only);
        }

        return ! $request->is(
            collect([
                'telescope-api*',
                'vendor/telescope*',
                (config('horizon.path') ?? 'horizon').'*',
                'vendor/horizon*',
            ])
                ->merge(config('telescope.ignore_paths', []))
                ->unless(is_null(config('telescope.path')), function ($paths) {
                    return $paths->prepend(config('telescope.path').'*');
                })
                ->all()
        );
    }

    protected function flushAuthenticationState(): void
    {
        if ($this->app->resolved('auth.driver')) {
            $this->app->forgetInstance('auth.driver');
        }

        if ($this->app->resolved('auth')) {
            with($this->app->make('auth'), function ($auth){
                $auth->forgetGuards();
            });
        }
    }

    protected function flushSessionState(): void
    {
        if (! $this->app->resolved('session')) {
            return;
        }

        $driver = $this->app->make('session')->driver();

        $driver->flush();
        $driver->regenerate();
    }
    protected function flushQueuedCookie(): void
    {
        if (! $this->app->resolved('cookie')) {
            return;
        }
        $this->app->make('cookie')->flushQueuedCookies();
    }

    protected function flushDatabase(): void
    {
        if (! $this->app->resolved('db')) {
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
        if (! $this->app->resolved('log')) {
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
        if (! $this->app->resolved('translator')) {
            return;
        }

        $config = $this->app->make('config');

        $translator =  $this->app->make('translator');

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
            if (! $file instanceof \SplFileInfo ||
                ! is_string($path = $file->getRealPath())) {
                continue;
            }

            clearstatcache(true, $path);

            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
