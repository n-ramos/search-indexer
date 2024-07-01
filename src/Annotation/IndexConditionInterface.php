<?php

namespace Nramos\SearchIndexer\Annotation;

interface IndexConditionInterface
{
    /**
     * Determines whether an entity should be indexed.
     *
     * @param object $entity the entity to check
     *
     * @return bool true if the entity should be indexed, false otherwise
     */
    public function __invoke(object $entity): bool;
}
