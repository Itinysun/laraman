<?php

namespace Itinysun\Laraman\Traits;

trait HasWebState
{
    /**
     * 清理web相关状态,移植自octane
     * @return void
     */
    public function cleanWebState(): void
    {
        $this->flushQueuedCookie();
        $this->flushSessionState();
        $this->flushAuthenticationState();
    }

    /**
     * @return void
     */
    protected function flushAuthenticationState(): void
    {
        if ($this->resolved('auth.driver')) {
            $this->forgetInstance('auth.driver');
        }

        if ($this->resolved('auth')) {
            with($this->make('auth'), function ($auth) {
                $auth->forgetGuards();
            });
        }
    }

    /**
     * @return void
     */
    protected function flushSessionState(): void
    {
        if (!$this->resolved('session')) {
            return;
        }

        $driver = $this->make('session')->driver();

        $driver->flush();
        $driver->regenerate();
    }

    /**
     * @return void
     */
    protected function flushQueuedCookie(): void
    {
        if (!$this->resolved('cookie')) {
            return;
        }
        $this->make('cookie')->flushQueuedCookies();
    }
}
