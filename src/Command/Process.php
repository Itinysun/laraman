<?php

namespace Itinysun\Laraman\Command;

use Illuminate\Console\Command;
use Workerman\Worker;

class Process extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected  $signature = 'laraman:process {name : The process name to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected  $description = 'run laraman process';

    public function handle(): void
    {
        //$this->info($this->argument('name'));


        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        if (is_callable('opcache_reset')) {
            opcache_reset();
        }

        $processName=$this->argument('name');

        worker_start($processName);
        Worker::runAll();
    }
}
