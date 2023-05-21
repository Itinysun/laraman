<?php

use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Itinysun\Laraman\Console\ConsoleApp;
use Workerman\Worker;


function app($abstract = null, array $parameters = [])
{
    $instance = Container::getInstance();
    if(!$instance->resolved('app'))
        $instance= ConsoleApp::getInstance();
    if (is_null($abstract)) {
        return $instance;
    }

    return $instance->make($abstract, $parameters);
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
 */
function worker_bind($worker, $class): void
{
    $callbackMap = [
        'onConnect',
        'onMessage',
        'onClose',
        'onError',
        'onBufferFull',
        'onBufferDrain',
        'onWorkerStop',
        'onWebSocketConnect',
        'onWorkerReload'
    ];
    foreach ($callbackMap as $name) {
        if (method_exists($class, $name)) {
            $worker->$name = [$class, $name];
        }
    }
    if (method_exists($class, 'onWorkerStart')) {
        call_user_func([$class, 'onWorkerStart'], $worker);
    }
}
/**
 * Start worker
 * @param $processName
 * @param $config
 * @return Worker
 */
function worker_start($processName, $config): Worker
{
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

    $worker->onWorkerStart = function ($worker) use ($config) {
        require_once base_path('/support/starter.php');
        if (isset($config['handler'])) {
            if (!class_exists($config['handler'])) {
                echo "process error: class {$config['handler']} not exists\r\n";
                return;
            }

            $instance = App::make($config['handler'], $config['constructor'] ?? []);
            worker_bind($worker, $instance);
        }
    };
    return $worker;
}

if (! function_exists('cpu_count')) {
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
