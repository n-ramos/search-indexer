<?php

namespace Nramos\SearchIndexer\Indexer;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @see SearchIndexerSubscriberTest
 */
#[AsDoctrineListener(event: Events::postPersist, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::preRemove, priority: 0, connection: 'default')]
class SearchIndexerSubscriber
{
    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly LoggerInterface $logger
    )
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove,
        ];
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        try {
            $this->indexEntity($args);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof IndexableEntityInterface) {
            return;
        }

        $reflectionClass = new ReflectionClass($entity);

        if ($reflectionClass->getAttributes(SearchIndex::class) && $reflectionClass->getAttributes(SearchIndex::class)[0]->newInstance()->autoIndex) {
            try {
                $this->indexer->remove($entity);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

        }
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        try {
            $this->indexEntity($args);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

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
            try {
                $this->indexer->index($entity);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

        }
    }
}
