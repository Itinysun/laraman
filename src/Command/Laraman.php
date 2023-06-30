<?php

namespace Itinysun\Laraman\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Itinysun\Laraman\Process\Monitor;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class Laraman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laraman {-d?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run laraman server';

    public const VERSION = "0.1.0";

    public const NAME = "laraman v" . self::VERSION."\r\n";

    /**
     * Execute the console command.
     * @throws Throwable
     */
    public function handle(): void
    {

        //如果使用artisan来启动，会导致容器混乱。
        if (!app()->resolved('laraman_console')) {
            Worker::safeEcho('please dont use artisan console, use "php laraman" to start up');
            return;
        }

        //打印版本号
        Worker::safeEcho(self::NAME);

        //创建运行目录
        make_dir(storage_path('laraman'));

        //读取公共配置
        $config = config('laraman.server');

        initWorkerConfig($config);

        //读取启动进程列表
        $processes = $config['processes'];

        if (isWindows()) {
            $processFiles = [];
            $monitor = null;
            foreach ($processes as $name) {
                if($name=='monitor'){
                    $option = config('laraman.monitor.options');
                    $monitor = new Monitor($option);
                }
                $processFiles[] = $this->buildBootstrapWindows($name);
            }
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
        } else {
            foreach ($processes as $process) {
                startProcessWithName($process);
            }
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
