<?php

namespace Itinysun\Laraman\Traits;

use Workerman\Worker;

trait HasWorkermanBuilder
{

    /**
     * if you need custom the build way ,override it
     * @throws \Throwable
     */
    public static function buildWorker($configName, $processName = null): Worker
    {
        $config = config("laraman.process.$processName.workerman");
        if (isset($config['count']) && $config['count'] == 0) {
            $config['count'] = cpu_count() * 4;
        }else{
            $config['count'] = 1;
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
