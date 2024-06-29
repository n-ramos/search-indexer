<?php

namespace Nramos\SearchIndexer\Annotation;


interface IndexConditionInterface
{
    /**
     * Determines whether an entity should be indexed.
     *
     * @param object $entity The entity to check.
     * @return bool True if the entity should be indexed, false otherwise.
     */
    public function __invoke(object $entity): bool;
}
