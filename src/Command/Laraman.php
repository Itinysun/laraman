<?php

namespace Itinysun\Laraman\Command;

use Illuminate\Console\Command;
use Throwable;
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
     * @throws Throwable
     */
    public function handle(): void
    {
        if(!app()->resolved('laraman_console')){
            $this->error('please dont use artisan console, use "php laraman" to start up');
            return;
        }
        $this->info(self::NAME);
        make_dir(storage_path('laraman'));
        $config = config('laraman.server');

        $processes = $config['processes'];
        try{

            $staticPropertyMap = [
                'pid_file',
                'status_file',
                'log_file'
            ];

            foreach ($staticPropertyMap as $property){
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
            Worker::$statusFile = $config['log_file'] ?? '';

            if (isWindows()) {
                $processFiles = [];
                foreach ($processes as $name){
                    $processFiles[]=$this->buildBootstrapWindows($name);
                }
                echo "\r\n";
                $resource = $this->open_processes($processFiles);
                while (1) {
                    sleep(1);
                    if (!empty($monitor) && $monitor->checkAllFilesChange()) {
                        $status = proc_get_status($resource);
                        $pid = $status['pid'];
                        shell_exec("taskkill /F /T /PID $pid");
                        proc_close($resource);
                        $resource = $this->open_processes($processFiles);
                    }
                }
            }else{
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

                if (property_exists(Worker::class, 'pid_file')) {
                    Worker::$statusFile = $config['pid_file'] ?? '';
                }
                if (property_exists(Worker::class, 'statusFile')) {
                    Worker::$statusFile = $config['status_file'] ?? '';
                }
                if (property_exists(Worker::class, 'stopTimeout')) {
                    Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
                }
                foreach ($processes as $process){
                    worker_start($process);
                }
            }
            Worker::runAll();
        }catch (Throwable $e){
            $this->error($e->getMessage());
            if(app()->hasDebugModeEnabled()){
                throw $e;
            }
        }
    }

    protected function open_processes($processFiles)
    {
        $cmd = '"' . PHP_BINARY . '" ' . implode(' ', $processFiles);
        $descriptors = [STDIN, STDOUT, STDOUT];
        $resource = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (!$resource) {
            exit("Can not execute $cmd\r\n");
        }
        return $resource;
    }
    protected function buildBootstrapWindows($processName): string
    {
        $basePath = base_path();
        $fileContent = <<<EOF
        #!/usr/bin/env php
        <?php
        /*
         * Please don't edit this file,this is auto generate by laraman
         */

        use Illuminate\Container\Container;

        require_once '$basePath/vendor/itinysun/laraman/fixes/WorkmanFunctions.php';

        require '$basePath/vendor/autoload.php';

        \$app = new \Itinysun\Laraman\Console\ConsoleApp('$basePath');
        \$status = \$app->runServerCommand(['laraman', 'process', '$processName']);
        exit(\$status);
        EOF;
        $processFile = storage_path('laraman') . DIRECTORY_SEPARATOR . "start_$processName.php";
        file_put_contents($processFile, $fileContent);
        return $processFile;
    }
}
