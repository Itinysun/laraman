<?php

namespace Itinysun\Laraman\Command;

use Illuminate\Console\Command;

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

    public function handle(): void
    {
        $this->info(json_encode(config('laraman')));
    }
}
