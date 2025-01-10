<?php

namespace Spy\TimelineBundle\Tests;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddFilterCompilerPass;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddRegistryCompilerPass;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddSpreadCompilerPass;
use Spy\TimelineBundle\SpyTimelineBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddLocatorCompilerPass;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddDeliveryMethodCompilerPass;
use Spy\TimelineBundle\DependencyInjection\Compiler\AddComponentDataResolver;

class SpyTimelineBundleTest extends TestCase
{
    public function testBuild()
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $matcher = $this->exactly(6);
        $containerBuilder
            ->expects($matcher)
            ->method('addCompilerPass')
            ->willReturnCallback(function ($value) use ($matcher, $containerBuilder) {
                match ($matcher->getInvocationCount()) {
                    1 => $this->assertInstanceOf(AddSpreadCompilerPass::class, $value),
                    2 => $this->assertInstanceOf(AddFilterCompilerPass::class, $value),
                    3 => $this->assertInstanceOf(AddRegistryCompilerPass::class, $value),
                    4 => $this->assertInstanceOf(AddDeliveryMethodCompilerPass::class, $value),
                    5 => $this->assertInstanceOf(AddLocatorCompilerPass::class, $value),
                    6 => $this->assertInstanceOf(AddComponentDataResolver::class, $value),
                };

                return $containerBuilder;
            });

        $bundle = new SpyTimelineBundle();
        $bundle->build($containerBuilder);
    }
}
