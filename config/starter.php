#!/usr/bin/env php
<?php
/*
 * Please don't edit this file,this is auto generate by laraman
 * 请勿编辑此文件，此文件为laraman自动生成，并且会被自动覆盖
 */

require_once __DIR__.'/vendor/itinysun/laraman/fixes/WorkmanFunctions.php';

require_once __DIR__.'/vendor/autoload.php';

include_once './vendor/itinysun/laraman/fixes/fix-symfony-stdout.php';

class_alias(\Symfony\Component\Console\Output\ConsoleOutputFix::class,Symfony\Component\Console\Output\ConsoleOutput::class);


try {
    //设置laravel需要的根路径
    \Itinysun\Laraman\Command\Configs::setBasePath(dirname(__FILE__));

    //准备workerman的运行环境
    \Itinysun\Laraman\Server\LaramanWorker::prepare();

    //识别是否需要执行子进程 process
    global $argv;
    $usingProcess = isset($argv[1]) && $argv[1]=='process';
    if($usingProcess){
        if(isset($argv[2])){
            $status = \Itinysun\Laraman\Command\Process::run($argv[2]);
        }else{
            throw new Exception('a process name is required when using single process mode!');
        }
    }else{
        $status = \Itinysun\Laraman\Command\Laraman::run();
    }

    exit($status);

} catch (Throwable $e) {
    // 渲染异常信息
    echo "发生异常：" . $e->getMessage() . "\n";
    echo "异常代码：" . $e->getCode() . "\n";
    echo "异常文件：" . $e->getFile() . "\n";
    echo "异常行号：" . $e->getLine() . "\n";
    echo "异常追踪：" . $e->getTraceAsString() . "\n";
}
