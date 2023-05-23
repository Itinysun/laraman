<?php

namespace Itinysun\Laraman\Command;

use Exception;
use Illuminate\Console\Command;
use Itinysun\Laraman\Console\ConsoleApp;
use Itinysun\Laraman\Server\HttpServer;
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
    public function handle(): void
    {
        if(!app()->resolved('laraman_console')){
            $this->error('please dont use artisan console, use "php laraman" to start up');
            return;
        }
        $this->info(self::NAME);

        $processes = config('laraman.server.processes');
        if (isWindows()) {
            $processFiles = [];
            foreach ($processes as $pname){
                $processFiles[]=$this->buildBootstrapWindows($pname);
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
        }

        Worker::runAll();
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
