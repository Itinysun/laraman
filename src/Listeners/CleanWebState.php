<?php

namespace Itinysun\Laraman\Listeners;

class CleanWebState
{
    public function handle($event): void
    {
        $event->app->cleanWebState();
    }
}
