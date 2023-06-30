<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * ConsoleOutput is the default class for all CLI output. It uses STDOUT and STDERR.
 *
 * This class is a convenient wrapper around `StreamOutput` for both STDOUT and STDERR.
 *
 *     $output = new ConsoleOutput();
 *
 * This is equivalent to:
 *
 *     $output = new StreamOutput(fopen('php://stdout', 'w'));
 *     $stdErr = new StreamOutput(fopen('php://stderr', 'w'));
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConsoleOutputHack extends StreamOutput implements ConsoleOutputInterface
{
    private OutputInterface $stderr;
    private array $consoleSectionOutputs = [];

    private static bool|null $daemonize = null;

    /**
     * @param int $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool|null $decorated Whether to decorate messages (null for auto-guessing)
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     */
    public function __construct(int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = null, OutputFormatterInterface $formatter = null)
    {
        parent::__construct($this->openOutputStream(), $verbosity, $decorated, $formatter);

        if (null === $formatter) {
            // for BC reasons, stdErr has it own Formatter only when user don't inject a specific formatter.
            $this->stderr = new StreamOutput($this->openErrorStream(), $verbosity, $decorated);

            return;
        }

        $actualDecorated = $this->isDecorated();
        $this->stderr = new StreamOutput($this->openErrorStream(), $verbosity, $decorated, $this->getFormatter());

        if (null === $decorated) {
            $this->setDecorated($actualDecorated && $this->stderr->isDecorated());
        }
    }

    /**
     * Creates a new output section.
     */
    public function section(): ConsoleSectionOutput
    {
        return new ConsoleSectionOutput($this->getStream(), $this->consoleSectionOutputs, $this->getVerbosity(), $this->isDecorated(), $this->getFormatter());
    }

    public function setDecorated(bool $decorated)
    {
        parent::setDecorated($decorated);
        $this->stderr->setDecorated($decorated);
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        parent::setFormatter($formatter);
        $this->stderr->setFormatter($formatter);
    }

    public function setVerbosity(int $level)
    {
        parent::setVerbosity($level);
        $this->stderr->setVerbosity($level);
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->stderr;
    }

    public function setErrorOutput(OutputInterface $error)
    {
        $this->stderr = $error;
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     */
    protected function hasStdoutSupport(): bool
    {
        if (static::$daemonize == null) {
            static::$daemonize = false;
            foreach ($_SERVER['argv'] as $arg) {
                if ($arg == '-d') {
                    static::$daemonize = true;
                    break;
                }
            }
        }
        return !static::$daemonize;
    }


    /**
     * @return resource
     */
    private function openOutputStream()
    {
        if (!$this->hasStdoutSupport()) {
            return fopen(storage_path('laraman') . '/web_stdout.log', 'a');
        }

        // Use STDOUT when possible to prevent from opening too many file descriptors
        return \defined('STDOUT') ? \STDOUT : (@fopen('php://stdout', 'w') ?: fopen('php://output', 'w'));
    }

    /**
     * @return resource
     */
    private function openErrorStream()
    {
        if (!$this->hasStdoutSupport()) {
            return fopen(storage_path('laraman') . '/web_stderr.log', 'a');
        }

        // Use STDERR when possible to prevent from opening too many file descriptors
        return \defined('STDERR') ? \STDERR : (@fopen('php://stderr', 'w') ?: fopen('php://output', 'w'));
    }
}
