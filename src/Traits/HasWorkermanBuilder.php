<?php

namespace Itinysun\Laraman\Traits;

use Itinysun\Laraman\Command\Configs;
use Itinysun\Laraman\Server\LaramanWorker as Worker;

trait HasWorkermanBuilder
{

    /**
     * if you need custom the build way ,override it
     * @throws \Throwable
     */
    public static function buildWorker($configName, $processName = null): Worker
    {
        $main = Configs::get($configName);

        $config = $main['workerman'];

        /*
         * check config['count'],
         * if not set or in windows,value should always be 1
         * if set to empty thing , value should be 4 times of cpu_count(),
        */
        if (isWindows() || !isset($config['count'])) {
            $config['count'] = 1;
        }else{
            $config['count'] = intval($config['count']) > 0 ? intval($config['count']) : cpu_count() * 4;
        }

        if (!$processName)
            $processName = $configName;


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

        return $worker;
    }
}
