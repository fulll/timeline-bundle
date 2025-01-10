<?php

namespace Spy\TimelineBundle\Tests\Units\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddSpreadCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddSpreadCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        //there are 3 spreaders, with 2 of them under the same priority
        $taggedServicesResult = array('foo.spread' => array(array('priority' => 10)), 'bar.spread' => array(), 'baz.spread' => array(array('priority' => 10)));

        $definition = $this->createMock(Definition::class);

        $definition
            ->expects($this->exactly(3))
            ->method('addMethodCall')
            ->willReturn($definition);


        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects($this->exactly(1))
            ->method('getAlias')
            ->with('spy_timeline.spread.deployer')
            ->willReturn(new \Symfony\Component\DependencyInjection\Alias('spy_timeline.spread.deployer'));

        $matcherGetDefinition = $this->exactly(4);
        $containerBuilder
            ->expects($matcherGetDefinition)
            ->method('getDefinition')
            ->willReturnCallback(function (string $value) use ($matcherGetDefinition, $definition) {
                match ($matcherGetDefinition->getInvocationCount()) {
                    1 =>  $this->assertEquals('spy_timeline.spread.deployer', $value),
                    2 =>  $this->assertEquals('foo.spread', $value),
                    3 =>  $this->assertEquals('bar.spread', $value),
                    4 =>  $this->assertEquals('baz.spread', $value),
                };

                return $definition;
            });

        $containerBuilder
            ->expects($this->exactly(1))
            ->method('findTaggedServiceIds')
            ->with('spy_timeline.spread')
            ->willReturn($taggedServicesResult);

        $compiler = new AddSpreadCompilerPass();
        $compiler->process($containerBuilder);
    }
}
