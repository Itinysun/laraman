<?php

namespace Itinysun\Laraman\Process;

use Workerman\Worker;

class ProcessBase
{
    public function __construct(){

    }
    protected Worker $worker;

    protected function onWorkerStart(Worker $worker): void
    {

    }


    private function _onWorkerStart(Worker $worker): void
    {
        $this->worker=$worker;
        $this->onWorkerStart($worker);
    }

    private function _onWorkerReload(Worker $worker): void
    {
        $this->worker=$worker;
    }

}
