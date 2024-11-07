<?php

namespace Nramos\SearchIndexer\Indexer;

class IndexableObjects
{
    public function __construct(
        private readonly array $indexedClasses
    ) {}

    public function getIndexedClasses(): array
    {
        return $this->indexedClasses;
    }
}
