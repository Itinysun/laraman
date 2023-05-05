<?php

namespace Itinysun\Laraman;

use App\Http\Kernel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Itinysun\Laraman\Http;
use Itinysun\Laraman\Server\ApiServer;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

class Laraman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laraman';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';



    public const VERSION = "0.6.1";

    public const NAME = "laraman v". self::VERSION;

    private const FUNCTIONS = ['header', 'header_remove', 'headers_sent', 'http_response_code', 'setcookie', 'session_create_id', 'session_id', 'session_name', 'session_save_path', 'session_status', 'session_start', 'session_write_close', 'session_regenerate_id', 'set_time_limit'];


    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        $this->init();

        $worker = $this->buildWorker();

        $worker->onWorkerStart = function ($worker) {
            $app = new ApiServer();
            $worker->onMessage = [$app, 'onMessage'];
            call_user_func([$app, 'onWorkerStart'], $worker);
        };


        /*
        // Windows does not support custom processes.
        if (DIRECTORY_SEPARATOR === '/') {
            foreach (config('process', []) as $processName => $config) {
                worker_start($processName, $config);
            }
            foreach (config('plugin', []) as $firm => $projects) {
                foreach ($projects as $name => $project) {
                    if (!is_array($project)) {
                        continue;
                    }
                    foreach ($project['process'] ?? [] as $processName => $config) {
                        worker_start("plugin.$firm.$name.$processName", $config);
                    }
                }
                foreach ($projects['process'] ?? [] as $processName => $config) {
                    worker_start("plugin.$firm.$processName", $config);
                }
            }
        }
        */

        Worker::runAll();

    }

    public function buildWorker(): Worker
    {
        Worker::$onMasterReload = function () {
            if (function_exists('opcache_get_status')) {
                if ($status = opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };
        $config = config('laraman');
        Worker::$pidFile = $config['pid_file'];
        Worker::$stdoutFile = $config['stdout_file'];
        Worker::$logFile = $config['log_file'];
        Worker::$eventLoopClass = $config['event_loop'] ?? '';
        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
        if (property_exists(Worker::class, 'statusFile')) {
            Worker::$statusFile = $config['status_file'] ?? '';
        }
        if (property_exists(Worker::class, 'stopTimeout')) {
            Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
        }
        $worker = new Worker($config['listen'], $config['context']);
        $propertyMap = [
            'name',
            'count',
            'user',
            'group',
            'reusePort',
            'transport',
            'protocol'
        ];
        foreach ($propertyMap as $property) {
            if (isset($config[$property])) {
                $worker->$property = $config[$property];
            }
        }
        return $worker;
    }



    public function init(): void
    {
        try {
            self::checkVersion();
            self::checkFunctionsDisabled();

            // OK initialize the functions
            require __DIR__ . '/AdapterFunctions.php';
            class_alias(\Itinysun\Laraman\Http::class, \Protocols\Http::class);
            Http::init();

        } catch (Exception $e) {
            fwrite(STDERR, self::NAME . ' Error:' . PHP_EOL);
            fwrite(STDERR, $e->getMessage());
            exit;
        }

        fwrite(STDOUT, self::NAME . ' OK' . PHP_EOL);
    }

    /**
     * Check PHP version
     *
     * @throws Exception
     * @return void
     */
    private function checkVersion(): void
    {
        if (\PHP_MAJOR_VERSION < 8) {
            throw new Exception("* PHP version must be 8 or higher." . PHP_EOL . "* Actual PHP version: " . \PHP_VERSION . PHP_EOL);
        }
    }

    /**
     * Check that functions are disabled in php.ini
     *
     * @throws Exception
     * @return void
     */
    private function checkFunctionsDisabled(): void
    {

        foreach (self::FUNCTIONS as $function) {
            if (\function_exists($function)) {
                throw new Exception("Functions not disabled in php.ini." . PHP_EOL . self::showConfiguration());
            }
        }
    }

    private static function showConfiguration(): string
    {
        $iniPath = \php_ini_loaded_file();
        $methods = \implode(',', self::FUNCTIONS);

        return "Add in file: $iniPath" . PHP_EOL . "disable_functions=$methods" . PHP_EOL;
    }
}
