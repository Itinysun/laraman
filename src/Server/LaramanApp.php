<?php

namespace Itinysun\Laraman\Server;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

class LaramanApp extends Application
{
    /**
     * laravel don't clear facade instance cache ,so we clear ourselves
     * @return void
     */
    public function forgetScopedInstances(): void
    {
        foreach ($this->scopedInstances as $scoped) {
            unset($this->instances[$scoped]);
            Facade::clearResolvedInstance($scoped);
        }

    }
}
