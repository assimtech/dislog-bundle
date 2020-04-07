<?php

declare(strict_types=1);

namespace Assimtech\DislogBundle\Command;

use Assimtech\Dislog\Handler\HandlerInterface;
use Assimtech\Sysexits;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCommand extends Command
{
    protected static $defaultName = 'assimtech:dislog:remove';

    protected function configure()
    {
        $this
            ->setDescription('Remove old api call logs')
        ;
    }

    private $handler;
    private $maxAge;

    public function __construct(
        HandlerInterface $handler,
        int $maxAge
    ) {
        parent::__construct();

        $this->handler = $handler;
        $this->maxAge = $maxAge;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->handler->remove($this->maxAge);

        return Sysexits::EX_OK;
    }
}
