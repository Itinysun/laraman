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

class Web extends ProcessBase
{
    protected ExceptionHandler $exceptionHandler;

    /**
     * OnMessage for workman ,here we use http protocol for http args
     * @param TcpConnection|mixed $connection
     */
    protected function onHttpMessage(mixed $connection,WorkmanRequest $workmanRequest): void
    {
        try{
            $request = Request::createFromBase(\Itinysun\Laraman\Http\Request::createFromWorkmanRequest($workmanRequest));
            try {
                if (StaticFileServer::$enabled && str_contains($request->path(), '.')) {
                    $result = StaticFileServer::tryServeFile($request);
                    if(null!==$result){
                        $this->send($connection,$result,$workmanRequest);
                        return ;
                    }
                }

                $this->refreshTelescope($request);

                $response = $this->getResponse($request);

                $this->send($connection, $response, $workmanRequest);
            } catch (Throwable $e) {
                report($e);
                $response = $this->exceptionHandler->render($request,$e);
                $this->send($connection, Response::fromLaravelResponse($response), $workmanRequest);
            }
        }catch (Throwable $e){
            $message = $this->app->hasDebugModeEnabled() ? $e->getMessage() : 'server error';
            $this->send($connection,new Response(500,[],$message),$workmanRequest);
        }
    }

    /**
     * OnWorkerStart.
     * @param Worker $worker
     * @return void
     */
    protected function onWorkerStart(Worker $worker): void
    {
        if(isset($this->params['static_file']))
            StaticFileServer::init($this->params['static_file']);


        // Heartbeat
        Timer::add(55, function () {
            $connections = DB::getConnections();
            if (!$connections) {
                return;
            }
            foreach ($connections as $key => $item) {
                if ($item->getDriverName() == 'mysql') {
                    $item->select('select 1',[],true);
                }
            }
        });
    }

    public static function buildWorker($configName, $processName = null): Worker{
        $worker = parent::buildWorker($configName,$processName);
        $options = config('laraman.process.'.$configName);
        if(isset($options['max_package_size']))
            TcpConnection::$defaultMaxPackageSize=$options['max_package_size'] ?? 10 * 1024 * 1024;
        return $worker;
    }

    /**
     * Send.
     * @param TcpConnection|mixed $connection
     * @param Response $response
     * @param WorkmanRequest $request
     * @return void
     */
    protected function send(mixed $connection, Response $response, WorkmanRequest $request): void
    {
        $keepAlive = $request->header('connection');

        if (($keepAlive === null && $request->protocolVersion() === '1.1')
            || $keepAlive === 'keep-alive' || $keepAlive === 'Keep-Alive'
        ) {
            $connection->send($response);
            return;
        }
        $connection->close($response);
    }

    public function getResponse(Request $request): Response
    {
        $response = $this->kernel->handle(
            $request
        );
        $this->kernel->terminate($request, $response);
        return Response::fromLaravelResponse($response);
    }

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
    protected static function requestIsToApprovedDomain($request): bool
    {
        return is_null(config('telescope.domain')) ||
            config('telescope.domain') !== $request->getHost();
    }

    /**
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
