<?php

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Itinysun\Laraman\Console\ConsoleApp;
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

function make_dir($path){
    clearstatcache($path);
    if(is_dir($path))
        return;
    if(isWindows()){
        mkdir($path);
    }else{
        if (!mkdir($path, 0777, true)) {
            throw new RuntimeException("Failed to create runtime logs directory. Please check the permission.");
        }
    }
}

/**
 * Copy dir
 * @param string $source
 * @param string $dest
 * @param bool $overwrite
 * @return void
 */
function copy_dir(string $source, string $dest, bool $overwrite = false): void
{
    if (is_dir($source)) {
        if (!is_dir($dest)) {
            mkdir($dest);
        }
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                copy_dir("$source/$file", "$dest/$file");
            }
        }
    } else if (file_exists($source) && ($overwrite || !file_exists($dest))) {
        copy($source, $dest);
    }
}

/**
 * Remove dir
 * @param string $dir
 * @return bool
 */
function remove_dir(string $dir): bool
{
    if (is_link($dir) || is_file($dir)) {
        return unlink($dir);
    }
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file") && !is_link($dir)) ? remove_dir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

/**
 * Bind worker
 * @param $worker
 * @param $class
 * @throws ReflectionException
 */
function worker_bind($worker, $class): void
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

    $methods = getWorkmanCallBacks($class);

    foreach ($callbackMap as $callback) {
        if (in_array($callback, $methods)) {
            $worker->$callback = [$class, '_' . $callback];
        }
    }
    if(in_array('onHttpMessage',$methods) || in_array('onTextMessage',$methods)){
        $worker->onMessage=[$class,'_onMessage'];
    }
    call_user_func([$class, '_onWorkerStart'], $worker);
}

function getWorkmanCallBacks($class): array
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
function worker_start(string $configName, string $processName = null): void
{
    if (!$processName)
        $processName = $configName;

    $config = config('laraman.process.' . $configName);

    if (empty($config))
        throw new Exception('process config not found for ' . $configName);

    if (!class_exists($config['handler'])) {
        throw new Exception("process error: class {$config['handler']} not exists");
    }
    $worker = call_user_func([$config['handler'], 'buildWorker'], $configName, $processName);

    $worker->onWorkerStart = function ($worker) use ($config) {
        register_shutdown_function(function ($startTime) {
            if (time() - $startTime <= 0.1) {
                sleep(1);
            }
        }, time());
        $instance = new $config['handler']($config['options'] ?? []);
        worker_bind($worker, $instance);
    };
}

if (!function_exists('cpu_count')) {
    function cpu_count(): int
    {
        // Windows does not support the number of processes setting.
        if (\DIRECTORY_SEPARATOR === '\\') {
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
}
