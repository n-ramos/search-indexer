<?php

namespace Nramos\SearchIndexer\Indexer;

interface IndexerInterface
{
    public function index(array $data): bool;
}
