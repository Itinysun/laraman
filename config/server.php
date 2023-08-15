<?php
/*
 * 这里是服务器公共配置文件
 */
return [

    /*
     * 自动启动进程名
     * 必须已有包含此名称的配置文件，可以参考 'web','monitor'
     */
    'processes'=>[
        'web','monitor'
    ],

    //是否支持端口复用，如果你需要不同process共用1个端口，请设置为true
    'reusePort' => false,

    //本程序运行目录，需要有读写权限，默认放入laravel的存储路径
    'runtime_path' => storage_path('laraman'),

    /*
     * 以下是 worker 全局唯一设定
     * @link https://www.workerman.net/doc/workerman/worker/pid-file.html
    */
    'pid_file' => storage_path('laraman') . '/web.pid',
    'status_file' => storage_path('laraman'). '/web.status',
    'stdout_file' => storage_path('laraman') . '/web_stdout.log',
    'log_file' => storage_path('laraman'). '/web.log',

    'event_loop' => '',

    /*
     * 平滑结束超时时间，单位为秒。
     * 当进程收到结束、重启信号后，等待当前任务（如果有）执行完毕的最长时间
     * 当进程的reloadable启用时需要配置此选项
    */
    'stop_timeout' => 2,

    /*
     * 以下是 connection 全局唯一设定
    */
    'max_package_size' => 10 * 1024 * 1024,


    //是否以守护模式运行，windows无效 等效 -d
    'daemonize' => false,

];
