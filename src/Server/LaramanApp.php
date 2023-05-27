<?php

namespace Itinysun\Laraman\Server;

use Illuminate\Foundation\Application;
use Itinysun\Laraman\Traits\HasBaseState;
use Itinysun\Laraman\Traits\HasCleanMode;
use Itinysun\Laraman\Traits\HasWebState;

/**
 *
 */
class LaramanApp extends Application
{
    use HasBaseState,HasWebState,HasCleanMode;
}
