<?php

namespace Itinysun\Laraman\Process;

use App\Exceptions\Handler;
use App\Http\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Itinysun\Laraman\Events\MessageReceived;
use Itinysun\Laraman\Listeners\OnMessageReceived;
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
            switch($this->worker->protocol){
                case 'Workerman\Protocols\Http':
                    //转换请求
                    $request = Request::createFromBase(\Itinysun\Laraman\Http\Request::createFromWorkmanRequest($data));

                    MessageReceived::dispatch($this->app,$this->app,$request);

                    $this->onHttpMessage($connection, $request);

                    //clean files after message
                    $this->flushUploadedFiles($request);
                    break;
                case 'Workerman\Protocols\Frame':
                case 'Workerman\Protocols\Text':
                case 'Workerman\Protocols\Websocket':
                case 'Workerman\Protocols\Ws':
                    $this->onTextMessage($connection, $data);
                    break;
                default:
                    $this->onCustomMessage($connection,$data);
            }
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

        Event::listen(MessageReceived::class,[OnMessageReceived::class,'handle']);

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
