<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle\Command;

use Assimtech\Dislog;
use Assimtech\Sysexits;
use Symfony\Component\Console;

class RemoveCommand extends Console\Command\Command
{
    protected static $defaultName = 'assimtech:dislog:remove';

    protected function configure()
    {
        $this
            ->setDescription('Remove old api call logs')
        ;
    }

    private Dislog\Handler\HandlerInterface $handler;
    private int $maxAge;

    public function __construct(
        Dislog\Handler\HandlerInterface $handler,
        int $maxAge
    ) {
        parent::__construct();

        $this->handler = $handler;
        $this->maxAge = $maxAge;
    }

    protected function execute(
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output
    ): int {
        $this->handler->remove($this->maxAge);

        return Sysexits::EX_OK;
    }
}
