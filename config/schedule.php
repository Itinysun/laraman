<?php
return [
    'handler' => \Itinysun\Laraman\Process\Schedule::class,
    'workerman'=>[
        'reloadable' => false,
    ],
    'options' => [
        'interval'=>60,
        'timeout'=>60
    ]
];
