<?php

namespace Itinysun\Laraman\Command;

use Illuminate\Console\Command;

class ConfigProxy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laraman-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get laraman configs with laravel';

    public function handle(): void
    {
        $this->info(json_encode(config('laraman')));
    }
}
