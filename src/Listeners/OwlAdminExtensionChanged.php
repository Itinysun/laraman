<?php

namespace Itinysun\Laraman\Listeners;

use Itinysun\Laraman\Server\LaramanWorker;

class OwlAdminExtensionChanged
{
    public function handle($event): void
    {
        LaramanWorker::safeEcho('检查到扩展变更，重启进程已加载变更');
        LaramanWorker::$needRestart=true;
    }
}
