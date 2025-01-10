<?php

namespace Spy\TimelineBundle\Tests\Units\Command;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\Command\DeployActionCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeployActionCommandTest extends TestCase
{
    public function testNoTimeline()
    {
        $actionManager = $this->createMock(\Spy\Timeline\Driver\ActionManagerInterface::class);
        $deployer      = $this->createMock(\Spy\Timeline\Spread\Deployer::class);
        $logger        = $this->createMock(\Psr\Log\LoggerInterface::class);

        $actionManager->method('findActionsWithStatusWantedPublished')->willReturn([]);

        $command = new DeployActionCommand($actionManager, $deployer, $logger);

        $application = new Application();
        $application->add($command);

        $command = $application->find('spy_timeline:deploy');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()], []);

        $this->assertEquals('There is 0 action(s) to deploy'.PHP_EOL.'Done'.PHP_EOL, $commandTester->getDisplay());
    }

    public function testOneTimeline()
    {
        $actionManager = $this->createMock(\Spy\Timeline\Driver\ActionManagerInterface::class);
        $deployer      = $this->createMock(\Spy\Timeline\Spread\Deployer::class);
        $action        = $this->createMock(\Spy\Timeline\Model\ActionInterface::class);
        $logger        = $this->createMock(\Psr\Log\LoggerInterface::class);

        $action->method('getId')->willReturn(1);
        $actionManager->method('findActionsWithStatusWantedPublished')->willReturn([$action]);

        $command = new DeployActionCommand($actionManager, $deployer, $logger);

        $application = new Application();
        $application->add($command);

        $command = $application->find('spy_timeline:deploy');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()], []);

        $this->assertEquals('There is 1 action(s) to deploy'.PHP_EOL.'Deploy action 1'.PHP_EOL.'Done'.PHP_EOL, $commandTester->getDisplay());
    }
}
