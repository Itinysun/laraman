<?php

namespace Itinysun\Laraman\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workerman\Connection\TcpConnection;

class MessageReceived
{
    use Dispatchable;
    public function __construct(
        public TcpConnection $connection,
        public $data
    ) {
    }
}
