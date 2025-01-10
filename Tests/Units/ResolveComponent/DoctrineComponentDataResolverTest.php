<?php

namespace Spy\TimelineBundle\Tests\Units\ResolveComponent;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\ResolveComponent\DoctrineComponentDataResolver as TestedModel;
use Spy\Timeline\ResolveComponent\TestHelper\User;
use Spy\Timeline\ResolveComponent\ValueObject\ResolveComponentModelIdentifier;

class DoctrineComponentDataResolverTest extends TestCase
{
    public function testObjectManagedByDoctrine()
    {
        $object = new User(5);
        $resolve = new ResolveComponentModelIdentifier($object);

        $classMetadata = $this->createMock(\Doctrine\Persistence\Mapping\ClassMetadata::class);
        $managerRegistry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $objectManager = $this->createMock(\Doctrine\Persistence\ObjectManager::class);

        $managerRegistry->method('getManagerForClass')->willReturn($objectManager);
        $objectManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifier')->willReturn(['id']);
        $classMetadata->method('getName')->willReturn('Spy\Timeline\ResolveComponent\TestHelper\User');

        $resolver = new TestedModel();
        $resolver->addRegistry($managerRegistry);

        $result = $resolver->resolveComponentData($resolve);

        $this->assertEquals('Spy\Timeline\ResolveComponent\TestHelper\User', $result->getModel());
        $this->assertEquals(5, $result->getIdentifier());
    }

    public function testObjectNotManagedByDoctrine()
    {
        $object = new User(5);
        $resolve = new ResolveComponentModelIdentifier($object);

        $managerRegistry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $resolver = new TestedModel();
        $resolver->addRegistry($managerRegistry);

        $result = $resolver->resolveComponentData($resolve);

        $this->assertEquals('Spy\Timeline\ResolveComponent\TestHelper\User', $result->getModel());
        $this->assertEquals(5, $result->getIdentifier());
    }

    public function testObjectNotManagedByDoctrineWithoutGetIdMethod()
    {
        $object = new \stdClass();
        $resolve = new ResolveComponentModelIdentifier($object);

        $managerRegistry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $resolver = new TestedModel();
        $resolver->addRegistry($managerRegistry);

        $this->expectException(\Spy\Timeline\Exception\ResolveComponentDataException::class);
        $this->expectExceptionMessage('Model must have a getId method.');

        $resolver->resolveComponentData($resolve);
    }

    public function testStringModelAndIdentifierGiven()
    {
        $model = 'foo';
        $identifier = array('foo' => 'bar');
        $resolve = new ResolveComponentModelIdentifier($model, $identifier);

        $managerRegistry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $resolver = new TestedModel();
        $resolver->addRegistry($managerRegistry);

        $result = $resolver->resolveComponentData($resolve);

        $this->assertEquals($model, $result->getModel());
        $this->assertEquals($identifier, $result->getIdentifier());
    }
}
