<?php

namespace Itinysun\Laraman\Traits;

use Workerman\Connection\TcpConnection;
use Itinysun\Laraman\Server\LaramanWorker as Worker;

trait HasWorkermanEvents
{
    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-worker-start.html
     * @param Worker $worker
     * @return void
     */
    protected function onWorkerStart(Worker $worker): void
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-connect.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onConnect(TcpConnection $connection)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-worker-reload.html
     * @param Worker $worker
     * @return void
     */
    protected function onWorkerReload(Worker $worker)
    {
    }


    /**
     * 自定义协议 会触发这个，请自行判断 $data 类型进行后续处理
     * @param TcpConnection $connection
     * @param $data
     * @return void
     */
    protected function onMessage(TcpConnection $connection, $data){

    }


    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-close.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onClose(TcpConnection $connection)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-buffer-full.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onBufferFull(TcpConnection $connection)
    {
    }

    /**
     * @link  https://www.workerman.net/doc/workerman/worker/on-buffer-drain.html
     * @param TcpConnection $connection
     * @return void
     */
    protected function onBufferDrain(TcpConnection $connection)
    {
    }

    /**
     * @link https://www.workerman.net/doc/workerman/worker/on-error.html
     * @param TcpConnection $connection
     * @param $code
     * @param $msg
     * @return void
     */
    protected function onError(TcpConnection $connection, $code, $msg)
    {

    }
}
