<?php

namespace Itinysun\Laraman\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workerman\Connection\TcpConnection;

class MessageDone
{
    use Dispatchable;
    public function __construct(
        public TcpConnection $connection,
        public $data
    ) {
    }
}
