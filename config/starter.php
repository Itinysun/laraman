#!/usr/bin/env php
<?php
/*
 * Please don't edit this file,this is auto generate by laraman
 */

require_once __DIR__.'/vendor/itinysun/laraman/fixes/WorkmanFunctions.php';

require __DIR__.'/vendor/autoload.php';

include_once './vendor/itinysun/laraman/fixes/fix-symfony-stdout.php';

\Itinysun\Laraman\Command\Configs::setBasePath(dirname(__FILE__));

try {
    $process = isset($argv[1]) ?? $argv[1]=='process' ? $argv[2] : false;
    if($process){
        $status = \Itinysun\Laraman\Command\Process::run($process);
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
