<?php

namespace Itinysun\Laraman\Server;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

/**
 *
 */
class LaramanApp extends Application
{
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
     * 重载绑定实例方法，这样我们可以实现洁癖模式，对于非系统服务，全都加入scopedInstances
     * @param $abstract
     * @param $concrete
     * @param $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false): void
    {
        parent::bind($abstract, $concrete, $shared);

        if(!$this->cleanMode || in_array($abstract,$this->scopedInstances))
            return;

        $basename = class_basename($abstract);
        if(!in_array($basename,$this->originBaseNames)){
            $this->scopedInstances[]=$abstract;
        }
    }

    /**
     * 洁癖模式
     * @var bool
     */
    private  bool $cleanMode = false;

    /**
     * @param mixed $switch
     * @return void
     */
    public function setCleanMode(mixed $switch=false): void
    {
        $this->cleanMode=boolval($switch);
    }

    /**
     * 框架原生的服务别名
     * @var array|string[]
     */
    protected array $originBaseNames = [
        'PackageManifest',
        'events',
        'log',
        'router',
        'url',
        'redirect',
        'ServerRequestInterfaceMix',
        'ResponseInterfacePackageManifest',
        'ResponseFactory',
        'CallableDispatcher',
        'ControllerDispatcherevents',
        'Kernel',
        'logKernel',
        'routerExceptionHandler',
        'url',
        'redirect',
        'ServerRequestInterface',
        'ResponseInterface',
        'ResponseFactory',
        'CallableDispatcher',
        'ControllerDispatcher',
        'Kernel',
        'Kernel',
        'ExceptionHandler',
        'env',
        'env',
        'auth',
        'auth.driver',
        'Authenticatable',
        'Gate',
        'RequirePassword',
        'cookie',
        'auth',
        'auth.driver',
        'Authenticatable',
        'Gate',
        'RequirePassword',
        'cookie',
        'db.factory',
        'db',
        'db.connection',
        'db.schema',
        'db.transactions',
        'Generator',
        'EntityResolver',
        'encrypter',
        'db.factory',
        'db',
        'db.connection',
        'filesdb.schema',
        'filesystemdb.transactions',
        'filesystem.diskGenerator',
        'filesystem.cloudEntityResolver',
        'encrypter',
        'ParallelTesting',
        'files',
        'filesystem',
        'filesystem.disk',
        'filesystem.cloud',
        'ParallelTesting',
        'MaintenanceModeManager',
        'MaintenanceMode',
        'Vite',
        'ChannelManager',
        'MaintenanceModeManager',
        'MaintenanceMode',
        'Vite',
        'ChannelManager',
        'session',
        'session.store',
        'StartSession',
        'view',
        'view.finder',
        'blade.compiler',
        'view.engine.resolver',
        'session',
        'session.store',
        'StartSession',
        'view',
        'view.finder',
        'blade.compiler',
        'view.engine.resolver',
        'EntriesRepository',
        'ClearableRepository',
        'PrunableRepository',
        'Provider',
        'EntriesRepository',
        'ExceptionHandlerClearableRepository',
        'PrunableRepository',
        'Provider',
        'Flare',
        'SentReports',
        'ConfigManager',
        'ExceptionHandler',
        'IgnitionConfig',
        'SolutionProviderRepository',
        'Flare',
        'Ignition',
        'SentReports',
        'ExceptionRenderer',
        'ConfigManager',
        'DumpRecorder',
        'LogRecorder',
        'QueryRecorder',
        'JobRecorder',
        'flare.logger',
        'IgnitionConfig',
        'SolutionProviderRepository',
        'Ignition',
        'ExceptionRenderer',
        'DumpRecorder',
        'LogRecorder',
        'QueryRecorder',
        'JobRecorder',
        'flare.logger',
        'cache',
        'cache.store',
        'cache.psr6',
        'memcached.connector',
        'RateLimiter',
        'cache',
        'cache.store',
        'cache.psr6',
        'memcached.connector',
        'RateLimiter',
        'redis',
        'redis.connection',
        'redis',
        'redis.connection',
        'MultiDumpHandler',
        'MultiDumpHandler',
        'queue',
        'queue.connection',
        'queuequeue.worker',
        'queue.connectionqueue.listener',
        'queue.workerqueue.failer',
        'queue.listener',
        'queue.failer',

    ];
}
