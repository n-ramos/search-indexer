<?php

namespace Nramos\SearchIndexer\Indexer;

interface IndexerInterface
{
    public function index(object $data): void;

    public function remove(IndexableEntityInterface $entityClass): void;
}
