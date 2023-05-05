<?php

namespace Itinysun\Laraman\Server;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Worker;

class Http
{

    /**
     * @var Worker|null
     */
    protected static ?Worker $worker = null;


    /**
     * @var string
     */
    protected static string $requestClass = '';

    protected static mixed $app;
    protected static Kernel $kernel;

    protected static PsrHttpFactory $psrHttpFactory;

    protected static Psr17Factory $psr17Factory;



    /**
     * App constructor.
     */
    public function __construct()
    {
        static::$psr17Factory = new Psr17Factory();
        static::$psrHttpFactory = new PsrHttpFactory(static::$psr17Factory , static::$psr17Factory , static::$psr17Factory , static::$psr17Factory );
    }

    /**
     * OnMessage.
     * @param TcpConnection|mixed $connection
     * @param Request|mixed $request
     * @return null
     */
    public function onMessage(mixed $connection, WorkermanRequest $request)
    {
        try {
            $laraRequest = ConvertWorkermanRequestToIlluminateRequest::convent(
                $request,
                PHP_SAPI
            );
            $response = App::get(Kernel::class)->handle($laraRequest);
            static::send($connection, $response, $request);
        } catch (Throwable $e) {
            $resp = new Response(json_encode([
                'code'=>500,
                'data'=>['code'=>$e->getCode(),'msg'=>$e->getMessage()],
                'msg'=>'unhandled exception from server',
                'request_id'=>''
            ]));
            static::send($connection, $resp, $request);
        }
        return null;
    }

    /**
     * OnWorkerStart.
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        static::$worker = $worker;
        \Workerman\Protocols\Http::requestClass(static::$requestClass);
    }

    /**
     * Send.
     * @param TcpConnection|mixed $connection
     * @param Response $response
     * @param WorkermanRequest $request
     * @return void
     */
    protected static function send(mixed $connection, Response $response, WorkermanRequest $request): void
    {
        $keepAlive = $request->header('connection');

        $psrResponse = static::$psrHttpFactory->createResponse($response);
//        if (($keepAlive === null && $request->protocolVersion() === '1.1')
//            || $keepAlive === 'keep-alive' || $keepAlive === 'Keep-Alive'
//        ) {
//            echo 'send';
//            $connection->send($response);
//            return;
//        }
        $connection->close($response);
    }
}
