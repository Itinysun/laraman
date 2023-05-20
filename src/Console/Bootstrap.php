<?php

namespace Itinysun\Laraman\Console;


define('LARAMAN_PATH',dirname(__FILE__));



require __DIR__.'/vendor/autoload.php';

require_once __DIR__.'/vendor/itinysun/laraman/src/fixes/WorkmanFunctions.php';

$runtimePath = LARAMAN_PATH.'/storage/laraman';

if(!is_dir($runtimePath))
    mkdir($runtimePath);
if(!is_dir($runtimePath.'/logs'))
    mkdir($runtimePath.'/logs');
$config = [
    'listen' => 'http://127.0.0.1:8000',
    'transport' => 'tcp',
    'context' => [],
    'name' => 'laraman',
    'count' => cpu_count() * 4,
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',
    'stop_timeout' => 2,
    'pid_file' => $runtimePath . '/laraman.pid',
    'status_file' => $runtimePath . '/laraman.status',
    'stdout_file' => $runtimePath. '/logs/stdout.log',
    'log_file' => $runtimePath. '/logs/laraman.log',
    'max_package_size' => 10 * 1024 * 1024
];
