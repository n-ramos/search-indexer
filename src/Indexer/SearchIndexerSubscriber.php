<?php

namespace Nramos\SearchIndexer\Indexer;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Exception;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

/**
 * @see SearchIndexerSubscriberTest
 */
class SearchIndexerSubscriber
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly IndexerInterface $indexer,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        try {
            $this->indexEntity($args);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof IndexableEntityInterface) {
            return;
        }

        $reflectionClass = new ReflectionClass($entity);

        if ($reflectionClass->getAttributes(SearchIndex::class) && $reflectionClass->getAttributes(SearchIndex::class)[0]->newInstance()->autoIndex) {
            try {
                $this->indexer->remove($entity);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        try {
            $this->indexEntity($args);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function indexEntity(PostPersistEventArgs|PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof IndexableEntityInterface) {
            return;
        }

        $reflectionClass = new ReflectionClass($entity);

        if ($reflectionClass->getAttributes(SearchIndex::class) && $reflectionClass->getAttributes(SearchIndex::class)[0]->newInstance()->autoIndex) {
            try {
                $this->indexer->index($entity);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
