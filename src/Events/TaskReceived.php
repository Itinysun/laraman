<?php

namespace Itinysun\Laraman\Events;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Events\Dispatchable;

class TaskReceived
{
    use Dispatchable;
    public function __construct(
        public Application $app,
        public Application $sandbox,
        public $data
    ) {
    }
}
