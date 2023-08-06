<?php

namespace Itinysun\Laraman\Command;

use Exception;
use Throwable;
use Workerman\Worker;

class Process
{

    /**
     * @throws Exception|Throwable
     */
    public static function run($processName=''): int
    {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        if(empty($processName)){
            if (isset($argv[2])) {
                $processName = $argv[2];
            } else {
                throw new Exception('please give the process name');
            }
        }

        //读取公共配置
        $config = Configs::get('server');

        //创建运行目录
        make_dir($config['runtime_path']);

        initWorkerConfig($config);

        if (is_callable('opcache_reset')) {
            opcache_reset();
        }

        startProcessWithName($processName);
        Worker::runAll();
        return 1;
    }
}
