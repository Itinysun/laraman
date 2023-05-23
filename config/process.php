<?php

use Itinysun\Laraman\Process\Web;
use Itinysun\Laraman\Process\Monitor;
use Workerman\Worker;

return [
    // File update detection and automatic reload
    'monitor' => [
        'handler' => Monitor::class,
        'workman'=>[
            'reloadable' => false,
        ],
        'options' => [
            // Monitor these directories
            'monitorDir' => [
                app_path(),
                config_path(),
                base_path() . '/packages',
                base_path() . '/resources',
                base_path() . '/routes',
                base_path() . '/.env',
            ],
            // Files with these suffixes will be monitored
            'monitorExtensions' => [
                'php', 'html', 'htm', 'env'
            ],
            'enable_file_monitor' => !Worker::$daemonize && DIRECTORY_SEPARATOR === '/',
            'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
        ]
    ],
    'web'=>[
        'workman'=>[
            'listen' => env('LARAMAN_WEB__LISTEN','http://127.0.0.1:8000'),
            'transport' => 'tcp',
            'context' => [],
            'name' => 'laraman',
            'count' => env('LARAMAN_WEB_WORKERS',0),
            'user' => '',
            'group' => '',
            'reusePort' => false,
            'reloadable' => true,
            'daemonize'=>false,
            'event_loop' => '',
            'stop_timeout' => 2,
            'runtime_path' => storage_path('laraman'),
            'pid_file' => storage_path('laraman') . '/web.pid',
            'status_file' => storage_path('laraman'). '/web.status',
            'stdout_file' => storage_path('laraman') . '/web_stdout.log',
            'log_file' => storage_path('laraman'). '/web.log',
        ],
        'options'=>[
            'max_package_size' => 10 * 1024 * 1024,
            'static_file'=>[
                'enable'=>true,
                'allowed'=>[
                    public_path()
                ],
                'support_php'=>false
            ]
        ],
        'handler'=> Web::class
    ]
];
