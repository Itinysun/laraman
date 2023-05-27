<?php

namespace Itinysun\Laraman\Process;

use Illuminate\Http\Request;
use Itinysun\Laraman\Events\MessageReceived;
use Itinysun\Laraman\Traits\HasWorkermanBuilder;
use Itinysun\Laraman\Traits\HasWorkermanEvents;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 *
 */
class ProcessBase
{
    use HasWorkermanEvents,HasWorkermanBuilder;


    /**
     * @var Worker
     */
    protected Worker $worker;


    /**
     * process config from 'options'
     * @var array|mixed
     */
    protected array $options = [];

    public function __construct($params = [])
    {
        $this->options = $params;
    }
    public function _onMessage(TcpConnection $connection, $data): void{
        MessageReceived::dispatch($connection,$data);
        $this->onMessage($connection,$data);
    }
    public function _onWorkerStart(Worker $worker): void{
        $this->onWorkerStart($worker);
    }

    public function _onWorkerReload(Worker $worker): void
    {
        $this->worker = $worker;
        $this->onWorkerReload($worker);
    }

    public function _onConnect(TcpConnection $connection): void
    {
        $this->onConnect($connection);
    }


    public function _onClose(TcpConnection $connection): void
    {
        $this->onClose($connection);
    }

    public function _onBufferFull(TcpConnection $connection): void
    {
        $this->onBufferFull($connection);
    }

    public function _onBufferDrain(TcpConnection $connection): void
    {
        $this->onBufferDrain($connection);
    }


    /**
     * @return void
     */
    protected function fixUploadedFile(): void
    {
        $fixesDir = dirname(__DIR__) . '../../fixes';
        if (!function_exists('\\Symfony\\Component\\HttpFoundation\\File\\is_uploaded_file')) {
            require $fixesDir . '/fix-symfony-file-validation.php';
        }
        if (!function_exists('\\Symfony\\Component\\HttpFoundation\\File\\move_uploaded_file')) {
            require $fixesDir . '/fix-symfony-file-moving.php';
        }
    }



    /**
     * @param Request $request
     * @return void
     */
    protected function flushUploadedFiles(Request $request): void
    {
        foreach ($request->files->all() as $file) {
            if (!$file instanceof \SplFileInfo ||
                !is_string($path = $file->getRealPath())) {
                continue;
            }

            clearstatcache(true, $path);

            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
