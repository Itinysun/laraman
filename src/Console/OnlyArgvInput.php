<?php

namespace Itinysun\Laraman\Console;

use Symfony\Component\Console\Input\ArgvInput;

class OnlyArgvInput extends ArgvInput
{
    public function getFirstArgument(): ?string{
        $command = parent::getFirstArgument();
        if($command!==null){
            return 'laraman:'.$command;
        }else{
            return 'laraman';
        }
    }
}
