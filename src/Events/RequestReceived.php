<?php

namespace Itinysun\Laraman\Events;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
class RequestReceived
{
    use Dispatchable;
    public function __construct(
        public Application $app,
        public Application $sandbox,
        public Request $request
    ) {
    }
}
