<?php

namespace Itinysun\Laraman\Traits;

use App\Exceptions\Handler;
use App\Http\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Itinysun\Laraman\Http\Response;
use Itinysun\Laraman\Server\LaramanApp;
use Itinysun\Laraman\Server\LaramanKernel;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

trait HasLaravelApplication
{
    protected Application $app;

    protected Kernel $kernel;

    protected ExceptionHandler $exceptionHandler;

    /**
     * 如果是HTTP协议，会触发这个
     * @link https://www.workerman.net/doc/workerman/worker/on-message.html
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    protected function onHttpMessage(TcpConnection $connection, Request $request)
    {
    }

    /**
     * 如果是workerman预定义的text类型协议，会触发这个
     * @link https://www.workerman.net/doc/workerman/worker/on-message.html
     * @param TcpConnection $connection
     * @param string $data
     * @return void
     */
    protected function onTextMessage(TcpConnection $connection, string $data)
    {
    }

    /**
     * @throws Throwable
     */
    public function _onMessage(TcpConnection $connection, $data): void
    {
        if ($this->worker->protocol != null ) {
            if($this->worker->protocol=='Workerman\Protocols\Http'){
                //转换请求
                $request = Request::createFromBase(\Itinysun\Laraman\Http\Request::createFromWorkmanRequest($data));

                $this->onHttpMessage($connection, $request);

                //clean files after message
                $this->flushUploadedFiles($request);

                return;
            }

            if(in_array($this->worker->protocol,['Workerman\Protocols\Frame','Workerman\Protocols\Text','Workerman\Protocols\Websocket','Workerman\Protocols\Ws']))
                $this->onTextMessage($connection, $data);
            else{
                $this->onMessage($connection,$data);
            }
        }else{

            $this->onMessage($connection,$data);
        }
    }

    public function _onWorkerStart(Worker $worker): void
    {
        $this->worker = $worker;

        $this->app = new LaramanApp(base_path());

        $this->app->setCleanMode($this->options['clearMode'] ?? false);

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

        if(isset($this->options['events']) && !empty($this->options['events'])){
            foreach ($this->options['events'] as $event=> $v){
                foreach (Arr::wrap($v) as $listener){
                    Event::listen($event,$listener);
                }
            }
        }
        /*
         Heartbeat
        数据库心跳，用来保持数据连接不断开。laravel有重连机制，虽然感觉好像没有必要，但是参考的前辈们都写了，我也加上了。
        如果你觉得不需要，可以注释掉。欢迎提供反馈。
        */
        Timer::add(5, function () {
            $connections = DB::getConnections();
            if (!$connections) {
                return;
            }
            try{
                foreach ($connections as $key => $item) {
                    if ($item->getDriverName() == 'mysql' ) {
                        $item->select('select 1',[],true);
                    }
                }
            }catch (Throwable $e){
                echo 'database heartbeat failed,maybe database has down';
            }

        });
        $this->onWorkerStart($worker);
    }

    /**
     * 获取运行结果，并转换为workerman格式
     * @param Request $request
     * @return Response
     */
    public function getResponse(Request $request): Response
    {
        $response = $this->kernel->handle(
            $request
        );
        $this->kernel->terminate($request, $response);
        return Response::fromLaravelResponse($response);
    }


    /**
     * 发送响应，提取自webman
     * @param TcpConnection|mixed $connection
     * @param Response $response
     * @param Request $request
     * @return void
     */
    protected function send(mixed $connection, Response $response, Request $request): void
    {
        $keepAlive = $request->header('connection');
        if (($keepAlive === null && $request->getProtocolVersion() === '1.1')
            || $keepAlive === 'keep-alive' || $keepAlive === 'Keep-Alive'
        ) {
            $connection->send($response);
            return;
        }
        $connection->close($response);
    }
}
