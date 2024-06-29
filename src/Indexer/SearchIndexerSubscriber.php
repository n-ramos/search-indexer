<?php

namespace Nramos\SearchIndexer\Indexer;


use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Nramos\SearchIndexer\Annotation\Map;
use ReflectionClass;

#[AsDoctrineListener(event: Events::postPersist, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 0, connection: 'default')]
class SearchIndexerSubscriber
{
    private IndexerInterface $indexer;

    public function __construct(IndexerInterface $indexer)
    {
        $this->indexer = $indexer;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {

        $this->indexEntity($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {

        $this->indexEntity($args);
    }

    private function indexEntity(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $reflectionClass = new ReflectionClass($entity);
       try {
           if ($reflectionClass->getAttributes(Map::class) && $reflectionClass->getAttributes(Map::class)[0]->newInstance()->autoIndex) {
               $this->indexer->index([
                   'entityClass' => get_class($entity),
                   'id' => $entity->getId(),
               ]);
           }
       } catch (\Exception $e) {
           // Do nothing
       }

    }

}