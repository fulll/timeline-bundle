<?php

namespace Spy\TimelineBundle\Tests\Units\Command;

use atoum\atoum\test;
use Spy\TimelineBundle\Command\SpreadListCommand as TestedCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class SpreadListCommand extends test
{
    public function beforeTestMethod($method)
    {
        define('STDIN', fopen("php://stdin", "r"));
    }

    public function testExecute()
    {
        $this->mockGenerator()->orphanize('__construct');
        $deployer = new \mock\Spy\Timeline\Spread\Deployer();
        $deployer->getMockController()->getSpreads = array();

        $command = new TestedCommand($deployer);

        $application = new Application();
        $application->add($command);

        $command = $application->find('spy_timeline:spreads');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()), array());

        $this->string($commandTester->getDisplay())
            ->isEqualTo('There is 0 timeline spread(s) defined'.PHP_EOL);

        // one spread
        $spread = new \mock\Spy\TimelineBundle\Spread\SpreadInterface();
        $deployer->getMockController()->getSpreads = array($spread);

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()), array());

        $this->string($commandTester->getDisplay())
            ->isEqualTo('There is 1 timeline spread(s) defined'.PHP_EOL.'- mock\Spy\TimelineBundle\Spread\SpreadInterface'.PHP_EOL);
    }
}
