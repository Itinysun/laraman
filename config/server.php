<?php

return [

    /*
     * 自动启动进程
     * 必须已在process.php中添加配置，此处填写配置名称
     */
    'processes'=>[
        'web','monitor'
    ],
    //运行目录
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
    //平滑结束超时时间，单位为秒。当进程收到结束、重启信号后，等待当前任务（如果有）执行完毕的最长时间
    'stop_timeout' => 2,

    /*
     * 以下是 connection 全局唯一设定
    */
    'max_package_size' => 10 * 1024 * 1024,

];
