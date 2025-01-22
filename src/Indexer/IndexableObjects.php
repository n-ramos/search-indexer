<?php

namespace Nramos\SearchIndexer\Indexer;

use Nramos\SearchIndexer\Annotation\IndexableEntityAttributesLoader;

class IndexableObjects
{
    public function __construct(
        private readonly IndexableEntityAttributesLoader $indexablesEntities,
        private readonly array $indexedClasses = []
    ) {}

    public function getIndexedClasses(): array
    {
        $indexedEntities = $this->indexablesEntities->load();

        return array_merge($this->indexedClasses, $indexedEntities);
    }
}
