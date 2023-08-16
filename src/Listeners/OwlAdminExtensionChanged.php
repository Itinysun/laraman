<?php

namespace Itinysun\Laraman\Listeners;

use Itinysun\Laraman\Server\LaramanWorker;

class OwlAdminExtensionChanged
{
    public function handle($event): void
    {
        LaramanWorker::safeEcho('检查到扩展变更，重启进程以加载变更'."\n\r");
        LaramanWorker::$needRestart=true;
    }
}
