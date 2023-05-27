<?php

namespace Itinysun\Laraman\Process;

use App\Exceptions\Handler;
use App\Http\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Itinysun\Laraman\Events\RequestReceived;
use Itinysun\Laraman\Events\TaskReceived;
use Itinysun\Laraman\Server\LaramanApp;
use Itinysun\Laraman\Server\LaramanKernel;
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

    protected Application $app;

    protected Kernel $kernel;
    /**
     * @var Worker
     */
    protected Worker $worker;

    protected ExceptionHandler $exceptionHandler;

    protected array $params = [];

    public function __construct($params = [])
    {
        $this->params = $params;
    }

    public function _onMessage(TcpConnection $connection, $data): void
    {
        if ($this->worker->protocol != null ) {
            if($this->worker->protocol=='Workerman\Protocols\Http'){
                //转换请求
                $request = Request::createFromBase(\Itinysun\Laraman\Http\Request::createFromWorkmanRequest($data));

                RequestReceived::dispatch($this->app,$this->app,$request);

                $this->onHttpMessage($connection, $request);

                //clean files after message
                $this->flushUploadedFiles($request);
                return;
            }
            TaskReceived::dispatch($this->app,$this->app,$data);
            if(in_array($this->worker->protocol,['Workerman\Protocols\Frame','Workerman\Protocols\Text','Workerman\Protocols\Websocket','Workerman\Protocols\Ws']))
                $this->onTextMessage($connection, $data);
            else{
                $this->onCustomMessage($connection,$data);
            }
        }else{
            TaskReceived::dispatch($this->app,$this->app,$data);
            $this->onCustomMessage($connection,$data);
        }
    }

    public function _onWorkerStart(Worker $worker): void
    {
        $this->worker = $worker;

        $this->app = new LaramanApp(base_path());

        $this->app->setCleanMode($this->params['clearMode'] ?? false);

        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            LaramanKernel::class
        );
        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );
        $this->app->singleton(
            ExceptionHandler::class,
            Handler::class
        );



        $this->kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        $this->exceptionHandler = $this->app->make(ExceptionHandler::class);

        $this->fixUploadedFile();

        if(isset($this->params['events']) && !empty($this->params['events'])){
            foreach ($this->params['events'] as $event=>$v){
                foreach (Arr::wrap($v) as $listener){
                    Event::listen($event,$listener);
                }
            }
        }

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
