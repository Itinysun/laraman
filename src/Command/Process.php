<?php

namespace Itinysun\Laraman\Command;

use Illuminate\Console\Command;
use Itinysun\Laraman\Console\ConsoleApp;
use Itinysun\Laraman\Process\ProcessBase;
use Itinysun\Laraman\Process\TestProcess;

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
    protected  $description = 'run laraman server';

    public function handle(): void
    {
        $this->info($this->argument('name'));

        $p = new \ReflectionClass(TestProcess::class);
        foreach ($p->getMethods() as $pm){
            $this->info($pm->name);
        }

        exit();



        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        if (is_callable('opcache_reset')) {
            opcache_reset();
        }
        $app = ConsoleApp::getInstance();
        $configuration = $app->make('config');
        $processName=$this->argument('name');

        $config = $configuration->get('laraman.process.'.$processName);


        $worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);
        $propertyMap = [
            'count',
            'user',
            'group',
            'reloadable',
            'reusePort',
            'transport',
            'protocol',
        ];
        $worker->name = $processName;
        foreach ($propertyMap as $property) {
            if (isset($config[$property])) {
                $worker->$property = $config[$property];
            }
        }

        $worker->onWorkerStart = function ($worker) use ($config) {
            register_shutdown_function(function ($startTime) {
                if (time() - $startTime <= 0.1) {
                    sleep(1);
                }
            }, time());
            if (isset($config['handler'])) {
                if (!class_exists($config['handler'])) {
                    echo "process error: class {$config['handler']} not exists\r\n";
                    return;
                }
                $instance = Container::make($config['handler'], $config['constructor'] ?? []);
                worker_bind($worker, $instance);
            }
        };
    }
}
