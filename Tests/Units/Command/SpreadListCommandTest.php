<?php

namespace Spy\TimelineBundle\Tests\Units\Command;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\Command\SpreadListCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SpreadListCommandTest extends TestCase
{
    public function testExecuteWithNoSpread()
    {
        $deployer = $this->createMock(\Spy\Timeline\Spread\Deployer::class);
        $deployer->method('getSpreads')->willReturn([]);

        $command = new SpreadListCommand($deployer);

        $application = new Application();
        $application->add($command);

        $command = $application->find('spy_timeline:spreads');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()], []);

        $this->assertEquals('There is 0 timeline spread(s) defined'.PHP_EOL, $commandTester->getDisplay());
    }

    public function testExecuteWithOneSpread()
    {
        // one spread
        $spread = $this->createMock(\Spy\Timeline\Spread\SpreadInterface::class);

        $deployer = $this->createMock(\Spy\Timeline\Spread\Deployer::class);
        $deployer->method('getSpreads')->willReturn([$spread]);

        $command = new SpreadListCommand($deployer);

        $application = new Application();
        $application->add($command);

        $command = $application->find('spy_timeline:spreads');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals('There is 1 timeline spread(s) defined'.PHP_EOL.'- '.get_class($spread).PHP_EOL, $commandTester->getDisplay());
    }
}
