<?php

namespace Spy\TimelineBundle\Tests\Units\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddLocatorCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        //there are 4 (3 unique) config locator services so there should be only 3 addLocator calls
        $configLocators = ['foo.service', 'bar.service'];
        $taggedServicesResult = ['baz.service' => [], 'foo.service' => []];

        //setup mocks
        $definition = $this->createMock(Definition::class);
        $definition
            ->expects($this->exactly(3))
            ->method('addMethodCall')
            ->with('addLocator', [$definition])
            ->willReturn($definition);

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects($this->exactly(1))
            ->method('findTaggedServiceIds')
            ->with('spy_timeline.filter.data_hydrator.locator')
            ->willReturn($taggedServicesResult);

        $containerBuilder->method('hasParameter')->willReturnCallback(function ($argument) {
            switch ($argument) {
                case "spy_timeline.filter.data_hydrator.locators_config":
                    return true;
            }
        });

        $matcherGetParameter = $this->exactly(1);
        $containerBuilder
            ->expects($matcherGetParameter)
            ->method('getParameter')
            ->willReturnCallback(function ($value) use ($matcherGetParameter, $configLocators) {
                match ($matcherGetParameter->getInvocationCount()) {
                    1 => $this->assertEquals('spy_timeline.filter.data_hydrator.locators_config', $value),
                };

                return $configLocators;
        });


        $matcherGetDefinition = $this->exactly(5);
        $containerBuilder
            ->expects($matcherGetDefinition)
            ->method('getDefinition')
            ->willReturnCallback(function (string $value) use ($matcherGetDefinition, $definition) {
                match ($matcherGetDefinition->getInvocationCount()) {
                    1, 4 =>  $this->assertEquals('foo.service', $value),
                    2 =>  $this->assertEquals('bar.service', $value),
                    3 =>  $this->assertEquals('baz.service', $value),
                    5 => $this->assertEquals('spy_timeline.filter.data_hydrator', $value),
                };

                return $definition;
            });


        $compiler = new AddLocatorCompilerPass();
        $compiler->process($containerBuilder);
    }
}
