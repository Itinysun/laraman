<?php

namespace Itinysun\Laraman\Command;

use Exception;
use Itinysun\Laraman\Server\LaramanWorker;
use Throwable;

/**
 * 单一 Process 运行入口
 */
class Process
{

    /**
     * @param string $processName process 的名称，如果为空，则从命令行中获取
     * @throws Exception|Throwable
     */
    public static function run(string $processName=''): int
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
        LaramanWorker::runAll();
        return 1;
    }
}
