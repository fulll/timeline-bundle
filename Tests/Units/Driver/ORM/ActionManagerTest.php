<?php

namespace Spy\TimelineBundle\Tests\Units\Driver\ORM;

use PHPUnit\Framework\TestCase;
use Spy\TimelineBundle\Driver\ORM\ActionManager;
use Spy\Timeline\ResolveComponent\ValueObject\ResolvedComponentData;
use Spy\Timeline\ResolveComponent\ValueObject\ResolveComponentModelIdentifier;

class ActionManagerTest extends TestCase
{
    public function testCreateComponent()
    {
        $model = 'user';
        $identifier = 0;
        $resolve = new ResolveComponentModelIdentifier($model, $identifier);

        $objectManager = $this->createMock(\Doctrine\Persistence\ObjectManager::class);
        $resultBuilder = $this->createMock(\Spy\Timeline\ResultBuilder\ResultBuilderInterface::class);
        $componentDataResolver = $this->createMock(\Spy\Timeline\ResolveComponent\ComponentDataResolverInterface::class);

        $componentDataResolver->method('resolveComponentData')->willReturn(new ResolvedComponentData($model, $identifier));

        $actionClass = 'Spy\Timeline\Model\Action';
        $componentClass = 'Spy\Timeline\Model\Component';
        $actionComponentClass = 'Spy\Timeline\Model\ActionComponent';
        $actionManager = new ActionManager($objectManager, $resultBuilder, $actionClass, $componentClass, $actionComponentClass);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Component data resolver not set');

        $actionManager->getComponentDataResolver();

        $actionManager->setComponentDataResolver($componentDataResolver);

        $result = $actionManager->createComponent($model, $identifier);

        $this->assertEquals($identifier, $result->getIdentifier());
        $this->assertEquals($model, $result->getModel());
        $this->assertNull($result->getData());
    }

    public function testfindOrCreateComponentWithExistingComponent()
    {
        $resolve = new ResolveComponentModelIdentifier('user', 1);
        $resolvedComponentData = new ResolvedComponentData('user', 1);

        $entityRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $objectManager = $this->createMock(\Doctrine\Persistence\ObjectManager::class);
        $resultBuilder = $this->createMock(\Spy\Timeline\ResultBuilder\ResultBuilderInterface::class);
        $componentDataResolver = $this->createMock(\Spy\Timeline\ResolveComponent\ComponentDataResolverInterface::class);
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $component = $this->createMock(\Spy\Timeline\Model\Component::class);

        $componentDataResolver->method('resolveComponentData')->willReturn($resolvedComponentData);
        $objectManager->method('getRepository')->willReturn($entityRepository);
        $entityRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $query->method('getOneOrNullResult')->willReturn($component);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $actionClass = \Spy\Timeline\Model\Action::class;
        $componentClass = \Spy\Timeline\Model\Component::class;
        $actionComponentClass = \Spy\Timeline\Model\ActionComponent::class;
        $actionManager = new ActionManager($objectManager, $resultBuilder, $actionClass, $componentClass, $actionComponentClass);

        $actionManager->setComponentDataResolver($componentDataResolver);

        $result = $actionManager->findOrCreateComponent('user', 1);

        $this->assertEquals($component, $result);
    }
}
