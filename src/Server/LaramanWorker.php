<?php

namespace Itinysun\Laraman\Server;

use Exception;
use Workerman\Worker;

/**
 *
 */
class LaramanWorker extends Worker
{
    /**
     * 去掉了默认的resetStd()
     * @throws Exception
     */
    public static function RunAll(): void
    {
        static::lock();
        static::daemonize();
        static::initWorkers();
        static::installSignal();
        static::saveMasterPid();
        static::lock(\LOCK_UN);
        static::displayUI();
        static::forkWorkers();
        //static::resetStd();
        static::monitorWorkers();
    }

    /**
     * 进行环境准备
     *
     * @return void
     * @throws Exception
     */
    public static function prepare(): void
    {
        static::checkSapiEnv();
        static::init();
        static::parseCommand();
        static::resetStd();
    }
}
