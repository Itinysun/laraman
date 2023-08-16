<?php

use Itinysun\Laraman\Events\MessageDone;
use Itinysun\Laraman\Events\MessageReceived;
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

        //是否支持平滑重启，如果是持续性任务，请设置为false
        'reloadable' => true,


        //运行命令使用的用户及组，windows无效
        'user' => '',
        'group' => '',
    ],
    'options' => [
        /*
         * 洁癖模式
         * 为了兼容未知内容，可以开启洁癖模式，除了laravel原生自带的服务，所有其他服务均被标记为scoped，然后在每次请求后自动销毁
         * 如果有应用是在laravel启动时进行动态加载一些参数到服务中，可能因服务重新启动导致这些参数丢失，例如owl-admin
        */
        'clearMode' => false,

        /*
         * 数据库心跳
         * 单位为秒，0 为禁用心跳
         * laravel有数据重连机制，所以如果没有出现问题，可以不用打开这个
         * */
        'db_heartbeat_interval'=>0,

        /*
         * 事件绑定
         * 可以进行自定义处理，请尽量不要抛出异常
         */
        'events' => [
            /*
             * 接受到请求后的事件，适用于WEB进程
             * 这会在接收到HTTP请求，进行处理之前触发
             * 已经按照octance进行了兼容，已自动兼容owl-admin和dcat-admin
            */
            RequestReceived::class => [
                CleanBaseState::class,
                CleanWebState::class,
            ],
            //兼容octance保留待用
            TaskReceived::class => [

            ],
            /*
             * 对于非WEB请求，请使用如下两个事件
             */
            MessageReceived::class=>[

            ],
            MessageDone::class=>[

            ],

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
            'support_php' => false,

            /*
             * 默认页面文件，当访问一个路径时，会尝试查找下面的默认页面文件
             * 请按照从上到下优先级来写
             * 如果不设置也不会列出目录
             * 如果是php文件，必须开启上面的support_php
             */
            'defaultPage'=>[
                'index.html',
                //'index.php',
            ]
        ]
    ],
    'handler' => Web::class
];
