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
            'enable_file_monitor' => true,
            'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
        ]
    ],
    'web'=>[
        'workman'=>[
            'listen' => env('LARAMAN_WEB__LISTEN','http://127.0.0.1:8000'),
            'transport' => 'tcp',
            'context' => [],
            'name' => 'laraman',
            //process需要的子进程数量，windows无效
            'count' => env('LARAMAN_WEB_WORKERS',0),
            //运行命令使用的用户及组，windows无效
            'user' => '',
            'group' => '',
            //是否支持端口复用，如果你需要不同process共用1个端口，请设置为true
            'reusePort' => false,
            //是否支持平滑重启，如果是持续性任务，请设置为false
            'reloadable' => true,
            //是否以守护模式运行，windows无效 等效 -d
            'daemonize'=>false,
            'event_loop' => '',
            //平滑结束超时时间，单位为秒。当进程收到结束、重启信号后，等待当前任务（如果有）执行完毕的最长时间
            'stop_timeout' => 2,
            'runtime_path' => storage_path('laraman'),
            //全局唯一设定
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
