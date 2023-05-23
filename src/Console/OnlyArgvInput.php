<?php

namespace Itinysun\Laraman\Console;

use Symfony\Component\Console\Input\ArgvInput;

/**
 * custom ArgvInput
 */
class OnlyArgvInput extends ArgvInput
{
    /**
     * with this we can short artisan command
     * before "php laraman laraman:process" after "php laraman process"
     * @return string|null
     */
    public function getFirstArgument(): ?string{
        $command = parent::getFirstArgument();
        if($command!==null){
            return 'laraman:'.$command;
        }else{
            return 'laraman';
        }
    }
}
