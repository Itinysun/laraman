<?php

namespace Itinysun\Laraman\Listeners;

class OnMessageReceived
{
    public function handle($event): void
    {
        $event->app->cleanBaseState();
        $event->app->cleanWebState();
    }
}
