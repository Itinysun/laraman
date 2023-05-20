<?php

namespace Itinysun\Laraman\server;

use App\Http\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;

class LaramanKernel extends Kernel
{
    public function __construct(Application $app, Router $router)
    {
        parent::__construct($app, $router);

        //warm providers with empty request
        $this->requestStartedAt = Carbon::now();
        $this->app->instance('request', new Request());
        $this->bootstrap();
    }
}
