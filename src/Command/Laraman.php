<?php

namespace Itinysun\Laraman\Command;

use Exception;
use Itinysun\Laraman\Process\Monitor;
use Itinysun\Laraman\Server\LaramanWorker;
use Throwable;

class Laraman
{
    public const VERSION = "2.0.5 beta";

    public const NAME = "laraman v" . self::VERSION . "\r\n";

    /**
     * 运行时主命令入口
     * @throws Throwable
     */
    public static function run(): int
    {
        //打印版本号
        LaramanWorker::safeEcho(self::NAME);

        //读取服务器公共配置
        $config = Configs::get('server');

        //创建运行目录
        make_dir(Configs::runtimePath());

        initWorkerConfig($config);

        //读取启动进程列表
        $processes = $config['processes'];

        if (isWindows()) {
            /*准备各进程启动文件*/
            $processFiles = [];
            $monitor = null;
            foreach ($processes as $name) {
                /*加载监控进程*/
                if ($name == 'monitor') {
                    $monitorConfig = Configs::get('monitor');
                    $option = $monitorConfig['options'];
                    $monitor = new Monitor($option);
                }
                $processFiles[] = self::buildBootstrapWindows($name);
            }
            $resource = self::open_processes($processFiles);
            while (1) {
                sleep(1);
                if (!empty($monitor) && $monitor->checkAllFilesChange()) {
                    $status = proc_get_status($resource);
                    $pid = $status['pid'];
                    shell_exec("taskkill /F /T /PID $pid");
                    proc_close($resource);
                    $resource = self::open_processes($processFiles);
                }
            }
        } else {
            /*仅当配置文件设置为true时才主动配置此选项，这样在用户手动输入-d时也可生效*/
            if($config['daemonize']){
                LaramanWorker::$daemonize=true;
            }
            foreach ($processes as $process) {
                startProcessWithName($process);
            }
        }

        LaramanWorker::runAll();
        return 1;
    }

    protected static function open_processes($processFiles)
    {
        $cmd = '"' . PHP_BINARY . '" ' . implode(' ', $processFiles);
        $descriptors = [STDIN, STDOUT, STDOUT];
        $resource = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (!$resource) {
            exit("Can not execute $cmd\r\n");
        }
        return $resource;
    }

    /**
     * @throws Exception
     */
    protected static function buildBootstrapWindows($processName): string
    {
        $basePath = Configs::getBasePath();
        $fileContent = <<<EOF
        #!/usr/bin/env php
        <?php
        /*
         * Please don't edit this file,this is auto generate by laraman
         */

        use Illuminate\Container\Container;

        require_once '$basePath/vendor/itinysun/laraman/fixes/WorkmanFunctions.php';

        require '$basePath/vendor/autoload.php';

        //准备workerman的运行环境
        \Itinysun\Laraman\Server\LaramanWorker::prepare();

        \Itinysun\Laraman\Command\Configs::setBasePath('$basePath');

        \$status = \Itinysun\Laraman\Command\Process::run('$processName');
        exit(\$status);
        EOF;
        $processFile = Configs::runtimePath("start_$processName.php");
        file_put_contents($processFile, $fileContent);
        return $processFile;
    }
}
