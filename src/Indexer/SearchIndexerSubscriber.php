<?php

namespace Nramos\SearchIndexer\Indexer;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Exception;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Nramos\SearchIndexer\Tests\Indexer\SearchIndexerSubscriberTest;
use ReflectionClass;

/**
 * @see SearchIndexerSubscriberTest
 */
#[AsDoctrineListener(event: Events::postPersist, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 0, connection: 'default')]
class SearchIndexerSubscriber
{
    private readonly IndexerInterface $indexer;

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

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->indexEntity($args);
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->indexEntity($args);
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    private function indexEntity(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof IndexableEntityInterface) {
            return;
        }

        $reflectionClass = new ReflectionClass($entity);

        if ($reflectionClass->getAttributes(SearchIndex::class) && $reflectionClass->getAttributes(SearchIndex::class)[0]->newInstance()->autoIndex) {
            $this->indexer->index([
                'entityClass' => $entity::class,
                'id' => $entity->getId(),
            ]);
        }
    }
}
