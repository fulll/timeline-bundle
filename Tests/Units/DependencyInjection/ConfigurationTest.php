<?php

namespace Spy\TimelineBundle\Tests\Units\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\DependencyInjection\Configuration as ConfigurationTested;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testNoConfiguration()
    {
        $this->assertEquals($this->getDefaultOutput(), $this->processConfiguration(array($this->getDefaultInput())));
    }

    public function testNoDriversAndNoManagers()
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "spy_timeline": Please define a driver or timeline_manager, action_manager');

        $this->processConfiguration(array(array()));
    }

    public function testMultipleDrivers()
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "spy_timeline.drivers": Please define only one driver.');

        $this->processConfiguration(array(array(
            'drivers' => array(
                'orm' => array(
                    'object_manager' => 'foo',
                ),
                'odm' => array(
                    'object_manager' => 'foo',
                ),
            ),
        )));
    }

    public function processConfiguration($config)
    {
        $processor     = new Processor();
        $configuration = new ConfigurationTested();

        return $processor->processConfiguration($configuration, $config);
    }

    protected function getDefaultInput()
    {
        return array(
            'timeline_manager' => 'foo',
            'action_manager' => 'foo',
        );
    }

    protected function getDefaultOutput()
    {
        return array(
            'timeline_manager' => 'foo',
            'action_manager' => 'foo',
            'notifiers' => [],
            'spread' => array(
                'on_subject' => true,
                'on_global_context' => true,
                'deployer' => 'spy_timeline.spread.deployer.default',
                'batch_size' => '50',
                'delivery' => 'immediate',
            ),
            'render' => array(
                'path' => '@SpyTimeline/Timeline',
                'fallback' => '@SpyTimeline/Timeline/default.html.twig',
                'resources' => array(
                    '@SpyTimeline/Action/components.html.twig',
                ),
            ),
            'query_builder' => array(
                'classes' => array(
                    'factory'  => 'Spy\Timeline\Driver\QueryBuilder\QueryBuilderFactory',
                    'asserter' => 'Spy\Timeline\Driver\QueryBuilder\Criteria\Asserter',
                    'operator' => 'Spy\Timeline\Driver\QueryBuilder\Criteria\Operator',
                ),
            ),
            'resolve_component' => array(
                'resolver' => 'spy_timeline.resolve_component.doctrine',
            ),
        );
    }
}
