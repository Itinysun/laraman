<?php

return [
    'listen' => env('LARAMAN_LISTEN','http://127.0.0.1:8000'),
    'transport' => 'tcp',
    'context' => [],
    'name' => 'laraman',
    'count' => env('LARAMAN_COUNT',0),
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',
    'stop_timeout' => 2,
    'pid_file' => storage_path('laraman') . '/laraman.pid',
    'status_file' => storage_path('laraman') . '/laraman.status',
    'stdout_file' => storage_path('logs') . '/stdout.log',
    'log_file' => storage_path('logs') . '/laraman.log',
    'max_package_size' => 10 * 1024 * 1024,
    'static_file'=>true
];
