<?php

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Itinysun\Laraman\Console\ConsoleApp;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;
use Workerman\Worker;

function isWindows(): bool
{
    return DIRECTORY_SEPARATOR !== "/";
}

function app($abstract = null, array $parameters = [])
{
    $instance = Container::getInstance();
    if (!$instance->resolved('app'))
        $instance = ConsoleApp::getInstance();
    if (is_null($abstract)) {
        return $instance;
    }
    return $instance->make($abstract, $parameters);
}

/**
 * 根据不同系统类型，生成文件夹
 * @param $path
 * @return void
 */
function make_dir($path): void
{
    clearstatcache($path);
    if (is_dir($path))
        return;
    if (isWindows()) {
        mkdir($path);
    } else {
        if (!mkdir($path, 0777, true)) {
            throw new RuntimeException("Failed to create runtime logs directory. Please check the permission.");
        }
    }
}

function marshalHeaders(array $headers): array
{
    $arr = [];
    foreach ($headers as $key => $v) {
        $keyWords = explode('-', $key);
        foreach ($keyWords as &$k)
            $k = ucwords($k);
        $newKey = implode('-', $keyWords);
        $arr[$newKey] = $v;
    }
    return $arr;
}

function initWorkerConfig(array $config): void
{
    $staticPropertyMap = [
        'pid_file',
        'status_file',
        'log_file'
    ];

    foreach ($staticPropertyMap as $property) {
        try {
            $path = $config[$property] ?? [];
            if (!empty($path)) {
                $dir = dirname($path);
                make_dir($dir);
            }
        } catch (\Throwable $e) {
            echo('Failed to create runtime logs directory. Please check the permission.');
            throw $e;
        }
    }

    Worker::$logFile = $config['log_file'] ?? '';

    TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;


    if (property_exists(Worker::class, 'stopTimeout')) {
        Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
    }

    if (!isWindows()) {
        Worker::$onMasterReload = function () {
            if (function_exists('opcache_get_status') && function_exists('opcache_invalidate')) {
                if ($status = \opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            \opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };

        Worker::$pidFile = $config['pid_file'] ?? '';
        Worker::$eventLoopClass = $config['event_loop'] ?? '';
        if (property_exists(Worker::class, 'statusFile')) {
            Worker::$statusFile = $config['status_file'] ?? '';
        }
        if (property_exists(Worker::class, 'stopTimeout')) {
            Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
        }
    }
}


/**
 * 事件绑定
 * @param $worker
 * @param $class
 * @throws ReflectionException
 */
function bindWorkerEvents($worker, $class): void
{
    $callbackMap = [
        'onConnect',
        'onClose',
        'onError',
        'onBufferFull',
        'onBufferDrain',
        'onWorkerStop',
        'onWebSocketConnect',
        'onWorkerReload'
    ];

    $methods = getWorkerCallBacks($class);

    foreach ($callbackMap as $callback) {
        if (in_array($callback, $methods)) {
            $worker->$callback = [$class, '_' . $callback];
        }
    }
    if (in_array('onHttpMessage', $methods) || in_array('onTextMessage', $methods) || in_array('onCustomMessage', $methods)) {
        $worker->onMessage = [$class, '_onMessage'];
    }
    call_user_func([$class, '_onWorkerStart'], $worker);
}

/**
 * 获取类型自有的原生事件方法
 * @param $class
 * @return array
 */
function getWorkerCallBacks($class): array
{
    try {
        $ref = new ReflectionClass($class);
        $methods = $ref->getMethods();
        $result = [];
        foreach ($methods as $m) {
            if ($m->class == $ref->name && str_starts_with($m->name, 'on')) {
                $result[] = $m->name;
            }
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Start worker
 * @param string $configName
 * @param string|null $processName
 * @throws Exception
 */
function startProcessWithName(string $configName, string $processName = null): void
{
    if (!$processName)
        $processName = $configName;

    $config = config('laraman.' . $configName);

    if (empty($config))
        throw new Exception('process config not found for ' . $configName);

    if (!class_exists($config['handler'])) {
        throw new Exception("process error: class {$config['handler']} not exists");
    }

    $worker = call_user_func([$config['handler'], 'buildWorker'], $configName, $processName);

    $worker->onWorkerStart = function ($worker) use ($config) {

        if (Arr::has($config, 'workerman.listen')) {
            register_shutdown_function(function ($startTime) {
                if (time() - $startTime <= 0.1) {
                    sleep(1);
                }
            }, time());
        }

        $instance = new $config['handler']($config['options'] ?? []);
        bindWorkerEvents($worker, $instance);
    };
}

function cpu_count(): int
{
    // Windows does not support the number of processes setting.
    if (isWindows()) {
        return 1;
    }
    $count = 4;
    if (\is_callable('shell_exec')) {
        if (\strtolower(PHP_OS) === 'darwin') {
            $count = (int)\shell_exec('sysctl -n machdep.cpu.core_count');
        } else {
            $count = (int)\shell_exec('nproc');
        }
    }
    return $count > 0 ? $count : 2;
}

