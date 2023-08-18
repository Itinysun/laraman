<?php

namespace Itinysun\Laraman\Process;

use Illuminate\Support\Facades\Artisan;
use Itinysun\Laraman\Server\LaramanWorker;
use Itinysun\Laraman\Server\LaramanWorker as Worker;
use Itinysun\Laraman\Traits\HasLaravelApplication;

class Schedule extends ProcessBase
{
    use HasLaravelApplication;

    protected function onWorkerStart(Worker $worker): void
    {
        Artisan::call('schedule:work');
    }

    protected function onWorkerReload(Worker $worker): void
    {
        LaramanWorker::safeEcho('schedule process has reload :' . $worker->id);
    }
}