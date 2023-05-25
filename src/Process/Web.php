<?php

namespace Itinysun\Laraman\Process;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Itinysun\Laraman\Http\Response;
use Itinysun\Laraman\Server\StaticFileServer;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkmanRequest;
use Workerman\Timer;
use Workerman\Worker;

/**
 *
 */
class Web extends ProcessBase
{
    /**
     * @var ExceptionHandler
     */
    protected ExceptionHandler $exceptionHandler;

    /**
     * OnMessage for workman ,here we use http protocol for http args
     * HTTP协议会触发onHttpMessage，这里写我们处理http请求的逻辑
     * @param TcpConnection|mixed $connection
     */
    protected function onHttpMessage(mixed $connection,Request $request): void
    {
        try{
            try {
                //首先检查是否是静态文件，如果是返回文件响应，如果不是则继续获取laravel响应
                if (StaticFileServer::$enabled && str_contains($request->path(), '.')) {
                    $result = StaticFileServer::tryServeFile($request);
                    if(null!==$result){
                        $this->send($connection,$result,$request);
                        return ;
                    }
                }

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
                $response = $this->exceptionHandler->render($request,$e);
                $this->send($connection, Response::fromLaravelResponse($response), $request);

            }
        }catch (Throwable $e){

            //运行到这里说明发生的问题是laravel框架无法处理的，需要自行处理异常
            $message = $this->app->hasDebugModeEnabled() ? $e->getMessage() : 'server error';
            $this->send($connection,new Response(500,[],$message),$request);
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
        if(isset($this->params['static_file']))
            StaticFileServer::init($this->params['static_file']);


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
    }


    /**
     * 自定义创建worker
     * @param $configName
     * @param $processName
     * @return Worker
     * @throws Throwable
     */
    public static function buildWorker($configName, $processName = null): Worker{
        $worker = parent::buildWorker($configName,$processName);
        $options = config('laraman.process.'.$configName);
        if(isset($options['max_package_size']))
            TcpConnection::$defaultMaxPackageSize=$options['max_package_size'] ?? 10 * 1024 * 1024;
        return $worker;
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
     * 重置telescope的状态
     * telescope会缓存是否记录请求的判断结果，需要每次判断是否需要记录本次请求
     * @param $request
     * @return void
     */
    protected function refreshTelescope($request): void
    {
        if (! config('telescope.enabled')) {
            return;
        }
        if(static::requestIsToApprovedDomain($request) &&
            static::requestIsToApprovedUri($request)){
            \Laravel\Telescope\Telescope::startRecording($loadMonitoredTags = false);
        }else{
            \Laravel\Telescope\Telescope::stopRecording();
        }
    }

    /**
     * 提取自telescope
     * @param $request
     * @return bool
     */
    protected static function requestIsToApprovedDomain($request): bool
    {
        return is_null(config('telescope.domain')) ||
            config('telescope.domain') !== $request->getHost();
    }

    /**
     * 提取自telescope，判断请求是否需要记录
     * Determine if the request is to an approved URI.
     *
     * @param Request $request
     * @return bool
     */
    protected static function requestIsToApprovedUri(Request $request): bool
    {
        Log::debug('telescope check url');

        if (! empty($only = config('telescope.only_paths', []))) {

            return $request->is($only);
        }

        return ! $request->is(
            collect([
                'telescope-api*',
                'vendor/telescope*',
                (config('horizon.path') ?? 'horizon').'*',
                'vendor/horizon*',
            ])
                ->merge(config('telescope.ignore_paths', []))
                ->unless(is_null(config('telescope.path')), function ($paths) {
                    return $paths->prepend(config('telescope.path').'*');
                })
                ->all()
        );
    }

}
