<?php

namespace Itinysun\Laraman\Command;

use Exception;
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

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);
        make_dir(storage_path('laraman'));

        if (is_callable('opcache_reset')) {
            opcache_reset();
        }

        $processName=$this->argument('name');

        startProcessWithName($processName);
        Worker::runAll();
    }
}
