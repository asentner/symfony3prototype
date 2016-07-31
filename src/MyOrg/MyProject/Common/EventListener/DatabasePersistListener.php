<?php

namespace MyOrg\MyProject\Common\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use MyOrg\MyProject\Common\DateTime\DateTimePlus;

class DatabasePersistListener
{
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        $isNewEntity = UnitOfWork::STATE_NEW === $event->getEntityManager()->getUnitOfWork()->getEntityState($entity);

        $metadata = $event->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($entity));

        if ($metadata->hasField('creationDate') && ($isNewEntity || null === $entity->getCreationDate())) {
            $entity->setCreationDate(new DateTimePlus());
        }

        if ($metadata->hasField('modificationDate')) {
            $entity->setModificationDate(new DateTimePlus());
        }
    }
}