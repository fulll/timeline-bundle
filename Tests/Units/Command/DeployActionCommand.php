<?php

namespace Spy\TimelineBundle\Tests\Units\Command;

use atoum\atoum\test;
use Spy\TimelineBundle\Command\DeployActionCommand as TestedCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeployActionCommand extends test
{
    public function testNoTimeline()
    {
        $actionManager = new \mock\Spy\Timeline\Driver\ActionManagerInterface();
        $this->mockGenerator()->orphanize('__construct');
        $deployer      = new \mock\Spy\Timeline\Spread\Deployer();
        $logger        = new \mock\Psr\Log\LoggerInterface();

        $actionManager->getMockController()->findActionsWithStatusWantedPublished = [];

        $command = new TestedCommand($actionManager, $deployer, $logger);

        $application = new Application();
        $application->add($command);

        $command = $application->find('spy_timeline:deploy');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()), []);

        $this->mock($actionManager)
            ->call('findActionsWithStatusWantedPublished')
            ->once();

        $this->string($commandTester->getDisplay())
            ->isEqualTo('There is 0 action(s) to deploy'.PHP_EOL.'Done'.PHP_EOL);
    }

    public function testOneTimeline()
    {
        $actionManager = new \mock\Spy\Timeline\Driver\ActionManagerInterface();
        $this->mockGenerator()->orphanize('__construct');
        $deployer      = new \mock\Spy\Timeline\Spread\Deployer();
        $action        = new \mock\Spy\Timeline\Model\ActionInterface();
        $logger        = new \mock\Psr\Log\LoggerInterface();

        $action->getMockController()->getId = 1;
        $actionManager->getMockController()->findActionsWithStatusWantedPublished = array($action);

        $command = new TestedCommand($actionManager, $deployer, $logger);

        $application = new Application();
        $application->add($command);

        $command = $application->find('spy_timeline:deploy');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()), []);

        $this->mock($actionManager)
            ->call('findActionsWithStatusWantedPublished')
            ->once();

        $this->string($commandTester->getDisplay())
            ->isEqualTo('There is 1 action(s) to deploy'.PHP_EOL.'Deploy action 1'.PHP_EOL.'Done'.PHP_EOL);
    }
}
