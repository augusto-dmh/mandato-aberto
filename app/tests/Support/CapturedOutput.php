<?php

namespace Tests\Support;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

/** A console output that keeps standard output and standard error apart, so a test can tell them apart. */
final class CapturedOutput extends BufferedOutput implements ConsoleOutputInterface
{
    private OutputInterface $error;

    public function __construct()
    {
        parent::__construct();
        $this->error = new BufferedOutput;
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->error;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->error = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        throw new \LogicException('sections are not captured');
    }

    public function errors(): string
    {
        return $this->error instanceof BufferedOutput ? $this->error->fetch() : '';
    }
}
