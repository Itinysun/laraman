<?php

namespace Itinysun\Laraman\Command;

use Exception;
use Illuminate\Console\Command;
use Itinysun\Laraman\Console\ConsoleApp;
use Itinysun\Laraman\fixes\Http;
use Itinysun\Laraman\Server\HttpServer;
use Itinysun\Laraman\Server\LaramanServer;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class Laraman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected  $signature = 'laraman';

    /**
     * The console command description.
     *
     * @var string
     */
    protected  $description = 'run laraman server';

    public const VERSION = "0.0.1";

    public const NAME = "laraman v". self::VERSION;
    /**
     * Execute the console command.
     */
    protected function startHttpServer($config): void
    {
        $this->info(self::NAME);

        if(!isset($config['count']) || $config['count']===0){
            $config['count'] = cpu_count()*4;
        }

        try{
            collect([$config['pid_file'],$config['status_file'],$config['stdout_file'],$config['log_file']])->map(function ($path){
                if(!empty($path)){
                    $dir = dirname($path);
                    if(!is_dir($dir)){
                        if(isWindows()){
                            mkdir($dir);
                        }else{
                            if(!mkdir($dir, 0777, true)) {
                                throw new Exception('create dir=> '.$dir. ' failed');
                            }
                        }
                        $this->info('create folder for workman runtime => '.$dir);
                    }
                }
            });
        }catch (Exception $e){
            $this->error('Failed to create runtime logs directory. Please check the permission.');
            $this->error($e->getMessage());
            return;
        }

        $worker = $this->buildWorker($config);

        if(null!==$worker){
            $worker->onWorkerStart = function ($worker) {
                $app = new HttpServer();
                $worker->onMessage = [$app, 'onMessage'];
                call_user_func([$app, 'onWorkerStart'], $worker);
            };
        }


        // Windows does not support custom processes.
        if (DIRECTORY_SEPARATOR === '/') {
            foreach (config('process', []) as $processName => $config) {
                worker_start($processName, $config);
            }
            foreach (config('plugin', []) as $firm => $projects) {
                foreach ($projects as $name => $project) {
                    if (!is_array($project)) {
                        continue;
                    }
                    foreach ($project['process'] ?? [] as $processName => $config) {
                        worker_start("plugin.$firm.$name.$processName", $config);
                    }
                }
                foreach ($projects['process'] ?? [] as $processName => $config) {
                    worker_start("plugin.$firm.$processName", $config);
                }
            }
        }

        Worker::runAll();

    }
    public function buildWorker($config): Worker|null
    {
        if(!isWindows()){
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

            Worker::$pidFile = $config['runtime_path'].'/laraman.pid';
            Worker::$stdoutFile = $config['runtime_path']. '/stdout.log';
            Worker::$logFile = $config['runtime_path'].'/laraman.log';
            Worker::$eventLoopClass = $config['event_loop'] ?? '';

            TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
            if (property_exists(Worker::class, 'statusFile')) {
                Worker::$statusFile = $config['status_file'] ?? '';
            }
            if (property_exists(Worker::class, 'stopTimeout')) {
                Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
            }
        }


        if ($config['listen']) {
            $worker = new Worker($config['listen'], $config['context']);
            $propertyMap = [
                'name',
                'count',
                'user',
                'group',
                'reusePort',
                'transport',
                'protocol'
            ];
            foreach ($propertyMap as $property) {
                if (isset($config[$property])) {
                    $worker->$property = $config[$property];
                }
            }
            return $worker;
        }
        return null;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if(!app()->resolved('laraman_console')){
            $this->error('please dont use artisan console, use "php laraman" to start up');
            return;
        }
        $app = ConsoleApp::getInstance();
        $configuration = $app->make('config');

        $config = $configuration->get('laraman.server');
        $this->startHttpServer($config);
    }
}
