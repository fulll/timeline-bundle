<?php

namespace Spy\TimelineBundle\Command;

use Spy\Timeline\Spread\Deployer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command will show all services which are defined as spread.
 */
#[AsCommand(
    name: 'spy_timeline:spreads',
    description: 'Deploy on spreads for waiting action',
)]
class SpreadListCommand extends Command
{
    private Deployer $deployer;

    public function __construct(
        Deployer $deployer
    ) {
        parent::__construct();
        $this->deployer = $deployer;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $spreads = $this->deployer->getSpreads();

        $output->writeln(sprintf('<info>There is %s timeline spread(s) defined</info>', count($spreads)));

        foreach ($spreads as $spread) {
            $output->writeln(sprintf('<comment>- %s</comment>', get_class($spread)));
        }

        return 0;
    }
}
