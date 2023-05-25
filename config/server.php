<?php

return [
    'processes'=>[
        'web','monitor'
    ],
    'runtime_path' => storage_path('laraman'),
    //全局唯一设定
    'pid_file' => storage_path('laraman') . '/web.pid',
    'status_file' => storage_path('laraman'). '/web.status',
    'stdout_file' => storage_path('laraman') . '/web_stdout.log',
    'log_file' => storage_path('laraman'). '/web.log',
    'event_loop' => '',
    //平滑结束超时时间，单位为秒。当进程收到结束、重启信号后，等待当前任务（如果有）执行完毕的最长时间
    'stop_timeout' => 2,

];
