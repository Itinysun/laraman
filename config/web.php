<?php

use Itinysun\Laraman\Events\RequestReceived;
use Itinysun\Laraman\Events\TaskReceived;
use Itinysun\Laraman\Listeners\CleanBaseState;
use Itinysun\Laraman\Listeners\CleanWebState;
use Itinysun\Laraman\Process\Web;

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
    'workerman' => [

        'listen' => env('LARAMAN_WEB_LISTEN', 'http://127.0.0.1:8000'),

        'transport' => 'tcp',

        'context' => [],

        'name' => 'laraman',

        /*
         * process需要的子进程数量，windows无效
         * if not set or in windows,value should always be 1
         * if set to empty thing , value should be 4 times of cpu_count()
         * */
        'count' => env('LARAMAN_WEB_WORKERS', 0),

        //运行命令使用的用户及组，windows无效
        'user' => '',
        'group' => '',

        //是否支持端口复用，如果你需要不同process共用1个端口，请设置为true
        'reusePort' => false,

        //是否支持平滑重启，如果是持续性任务，请设置为false
        'reloadable' => true,

        //是否以守护模式运行，windows无效 等效 -d
        'daemonize' => false,

    ],
    'options' => [
        /*
         * 洁癖模式
         * 为了兼容未知内容，可以开启洁癖模式，除了laravel原生自带的服务，所有其他服务均被标记为scoped，然后在每次请求后自动销毁
        */
        'clearMode' => false,

        /*
         * 数据库心跳
         * 单位为秒，0 为禁用心跳
         * */
        'db_heartbeat_interval'=>59,
        'events' => [
            RequestReceived::class => [
                CleanBaseState::class,
                CleanWebState::class,

                /*如果你使用DcatAdmin请取消下面这行注释*/
                // \Dcat\Admin\Octane\Listeners\FlushAdminState::class

            ],
            TaskReceived::class => [

            ]
        ],
        /*静态文件配置*/
        'static_file' => [
            //是否启用静态文件服务器，如果不启用，无法访问 css\js\img 等静态文件
            'enable' => true,
            /*
             * 允许访问的路径，可以添加多个。
             * 注意，此选项非常 非常 非常 重要。请确保这些目录不会包含敏感信息
             */
            'allowed' => [
                public_path()
            ],
            //是否支持获取php文件结果，如果启用则获取运行后结果，如果不启用则返回错误
            'support_php' => false
        ]
    ],
    'handler' => Web::class
];
