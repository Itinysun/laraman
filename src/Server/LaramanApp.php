<?php

namespace Itinysun\Laraman\Server;

use Illuminate\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
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
