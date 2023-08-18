<?php

namespace Itinysun\Laraman\Process;

use Illuminate\Http\Request;
use Itinysun\Laraman\Events\MessageDone;
use Itinysun\Laraman\Events\MessageReceived;
use Itinysun\Laraman\Server\LaramanWorker;
use Itinysun\Laraman\Traits\HasWorkermanBuilder;
use Itinysun\Laraman\Traits\HasWorkermanEvents;
use Workerman\Connection\TcpConnection;
use Itinysun\Laraman\Server\LaramanWorker as Worker;
use function dirname;

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
        MessageDone::dispatch($connection,$data);
        $this->checkRestart();
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
     * 检查框架是否需要重启则，请避免在高并发请求使用
     * @return void
     */
    protected function checkRestart(): void
    {
        if(LaramanWorker::$needRestart){
            LaramanWorker::$needRestart=false;
            LaramanWorker::stopAll();
        }
    }
}
