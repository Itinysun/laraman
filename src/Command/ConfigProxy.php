<?php

namespace Itinysun\Laraman\Command;

use Illuminate\Console\Command;
use JetBrains\PhpStorm\NoReturn;

class ConfigProxy extends Command
{
    /**
     * 使用这个命令来获取laravel运行环境下的配置
     *
     * @var string
     */
    protected $signature = 'laraman-config';

    /**
     * 使用这个命令来获取laravel的配置
     *
     * @var string
     */
    protected $description = 'get laraman configs with laravel';

    #[NoReturn] public function handle(): void
    {
        exit(json_encode(config('laraman')));
    }
}
