<?php

namespace Itinysun\Laraman\Listeners;

class CleanBaseState
{
    public function handle($event): void
    {
        $event->app->cleanBaseState();
    }
}
