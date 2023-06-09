<?php

use Itinysun\Laraman\Process\Monitor;

/*
 * 这里是进程配置文件
 * 配置文件格式：
 * 'process name'=>[
 *      'handler'=>class name, 必须，写进程类名。必须继承 ProcessBase 类
 *      'options'= [ 进程类构造函数的参数，用于进程内部使用
 *          'events'=>[event name =>array(listeners)] 可选，订阅事件。
 *          'clearMode'=>bool , 可选，默认false，是否开启洁癖模式
 *       ],
 *      'workerman'=>array config 可选，构造 worker时的参数，参考workerman官方手册，如果是全局属性，请在server中配置

 * ]
 *
 */
return [
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
];
