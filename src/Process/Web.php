<?php

namespace Itinysun\Laraman\Process;

use Illuminate\Http\Request;
use Itinysun\Laraman\Events\RequestReceived;
use Itinysun\Laraman\Http\Response;
use Itinysun\Laraman\Server\StaticFileServer;
use Itinysun\Laraman\Traits\HasLaravelApplication;
use Itinysun\Laraman\Traits\HasRefreshTelescope;
use Throwable;
use Workerman\Connection\TcpConnection;
use Itinysun\Laraman\Server\LaramanWorker as Worker;

/**
 *
 */
class Web extends ProcessBase
{
    use HasRefreshTelescope, HasLaravelApplication;


    /**
     * OnMessage for workman ,here we use http protocol for http args
     * HTTP协议会触发onHttpMessage，这里写我们处理http请求的逻辑
     * @param TcpConnection|mixed $connection
     * @param Request $request
     * @return void
     * @throws Throwable
     */
    protected function onHttpMessage(mixed $connection, Request $request): void
    {
        try {
            //首先检查是否是静态文件，如果是返回文件响应，如果不是则继续获取laravel响应
            if (StaticFileServer::$enabled) {
                $result = StaticFileServer::tryServeFile($request);
                if (null !== $result) {
                    $this->send($connection, $result, $request);
                    return;
                }
            }

            RequestReceived::dispatch($this->app, $this->app, $request);

            //如果启用了telescope，需要每次重置状态
            $this->refreshTelescope($request);

            //获取响应
            $response = $this->getResponse($request);

            //发送响应
            $this->send($connection, $response, $request);


        } catch (Throwable $e) {

            //记录异常
            report($e);

            //使用原生laravel的方式渲染异常并发送异常，请查看laravel手册
            $response = $this->exceptionHandler->render($request, $e);
            $this->send($connection, Response::fromLaravelResponse($response)->withStatus(500), $request);
        }
    }

    /**
     * OnWorkerStart.
     * @param Worker $worker
     * @return void
     */
    protected function onWorkerStart(Worker $worker): void
    {
        //读取配置，初始化静态文件服务
        if (isset($this->options['static_file']))
            StaticFileServer::init($this->options['static_file']);

    }
}
