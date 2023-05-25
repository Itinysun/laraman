<?php

use Itinysun\Laraman\Process\Web;
use Itinysun\Laraman\Process\Monitor;
use Workerman\Worker;

return [
    // File update detection and automatic reload
    //热重载：监控文件变动后自动重启进程
    'monitor' => [
        'handler' => Monitor::class,
        'workerman'=>[
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
            //是否启用文件监控 需要非demon模式
            'enable_file_monitor' => true,
            //是否启用占用内存监控 需要linux系统
            'enable_memory_monitor' => true,
        ]
    ],
    'web'=>[
        'workerman'=>[
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
