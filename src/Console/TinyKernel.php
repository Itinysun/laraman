<?php

namespace Itinysun\Laraman\Console;


use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\InteractsWithTime;
use Itinysun\Laraman\Command\Laraman;
use Itinysun\Laraman\Command\Process;
use Itinysun\Laraman\Console\TinyArtisan as Artisan;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * we need a tiny kernel,with less bootstrapper ,within config services,
 * and we need to run our command only , and no events
 */
class TinyKernel implements KernelContract
{
    use InteractsWithTime;

    /**
     * The application implementation.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The Artisan application instance.
     *
     * @var TinyArtisan|null
     */
    protected ?TinyArtisan $artisan = null;

    /**
     * The Artisan commands provided by the application.
     *
     * @var array
     */
    protected array $commands = [];

    /**
     * Indicates if the Closure commands have been loaded.
     *
     * @var bool
     */
    protected bool $commandsLoaded = false;

    /**
     * The bootstrap classes for the application.
     *
     * @var string[]
     */
    protected array $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    ];

    /**
     * Create a new console kernel instance.
     *
     * @param Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        if (! defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'artisan');
        }
        $this->app = $app;
        $this->bootstrap();
    }


    /**
     * Run the console application.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface|null  $output
     * @return int
     */
    public function handle($input, $output = null): int
    {
        try {
            return $this->getArtisan()->run($input, $output);
        } catch (Throwable $e) {
            $this->reportException($e);
            $this->renderException($output, $e);
            return 1;
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->registerCommand(new Laraman());
        $this->registerCommand(new Process());
    }

    /**
     * Register the given command with the console application.
     *
     * @param Command $command
     * @return void
     */
    public function registerCommand(Command $command): void
    {
        $this->getArtisan()->add($command);
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  OutputInterface|null  $outputBuffer
     * @return int
     *
     * @throws CommandNotFoundException
     */
    public function call($command, array $parameters = [], $outputBuffer = null): int
    {
        $this->bootstrap();
        return $this->getArtisan()->call($command, $parameters, $outputBuffer);
    }


    /**
     * Get all the commands registered with the console.
     *
     * @return array
     */
    public function all(): array
    {
        $this->bootstrap();

        return $this->getArtisan()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string
    {
        $this->bootstrap();

        return $this->getArtisan()->output();
    }

    /**
     * Bootstrap the application for artisan commands.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }

        if (! $this->commandsLoaded) {
            $this->commands();
            $this->commandsLoaded = true;
        }
    }


    /**
     * Get the Artisan application instance.
     *
     * @return TinyArtisan
     */
    protected function getArtisan(): TinyArtisan
    {

        if (is_null($this->artisan)) {
            $this->artisan = (new Artisan($this->app, $this->app->version()))
                                    ->resolveCommands($this->commands)
                                    ->setContainerCommandLoader();
        }
        return $this->artisan;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param Throwable $e
     * @return void
     */
    protected function reportException(Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the given exception.
     *
     * @param OutputInterface $output
     * @param Throwable $e
     * @return void
     */
    protected function renderException(OutputInterface $output, Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->renderForConsole($output, $e);
    }

    public function queue($command, array $parameters = [])
    {
        // TODO: Implement queue() method.
    }

    public function terminate($input, $status)
    {
        // TODO: Implement terminate() method.
    }
}
