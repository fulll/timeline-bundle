<?php

namespace Spy\TimelineBundle\Driver\Doctrine\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityNotFoundException;
use Spy\Timeline\Model\ComponentInterface;

class PostLoadListener implements EventSubscriber
{
    /**
     * @param PostLoadEventArgs $eventArgs eventArgs
     */
    public function postLoad(PostLoadEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof ComponentInterface || null != $entity->getData()) {
            return;
        }

        try {
            $entity->setData(
                $eventArgs->getObjectManager()->getReference(
                    $entity->getModel(),
                    $entity->getIdentifier()
                )
            );
        } catch (EntityNotFoundException $e) {
            // if entity has been deleted ...
        } catch (MappingException $e) {
            // if entity is not a valid entity or mapped super class
        }
    }

    /**
     * @return array<string>
     */
    public function getSubscribedEvents(): array
    {
        return array('postLoad');
    }
}
