<?php

namespace Itinysun\Laraman\Server;

use Exception;
use Workerman\Worker;

/**
 *
 */
class LaramanWorker extends Worker
{
    public static bool $needRestart = false;
    /**
     * 去掉了默认的resetStd()
     * @throws Exception
     */
    public static function RunAll(): void
    {

        static::parseCommand();
        static::init();
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
        static::resetStd();
    }
}
