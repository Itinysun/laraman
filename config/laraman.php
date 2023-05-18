<?php
if (! function_exists('cpu_count')) {
    function cpu_count(): int
    {
        // Windows does not support the number of processes setting.
        if (\DIRECTORY_SEPARATOR === '\\') {
            return 1;
        }
        $count = 4;
        if (\is_callable('shell_exec')) {
            if (\strtolower(PHP_OS) === 'darwin') {
                $count = (int)\shell_exec('sysctl -n machdep.cpu.core_count');
            } else {
                $count = (int)\shell_exec('nproc');
            }
        }
        return $count > 0 ? $count : 2;
    }
}
return [
    'listen' => 'http://127.0.0.1:8787',
    'transport' => 'tcp',
    'context' => [],
    'name' => 'laraman',
    'count' => cpu_count() * 4,
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',
    'stop_timeout' => 2,
    'pid_file' => storage_path('laraman') . '/laraman.pid',
    'status_file' => storage_path('laraman') . '/laraman.status',
    'stdout_file' => storage_path('laraman') . '/logs/stdout.log',
    'log_file' => storage_path('laraman') . '/logs/laraman.log',
    'max_package_size' => 10 * 1024 * 1024,
    'static_file'=>true
];
