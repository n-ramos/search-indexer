<?php

namespace Nramos\SearchIndexer\Indexer;

use Nramos\SearchIndexer\Filter\SearchFilterInterface;

interface SearchClientInterface
{
    public function put(string $index, array $data): array;
    public function delete(string $index, int $id): array;
    public function clear(string $index): array;
    public function search(string $indexName, string $query, SearchFilterInterface $filters = null, int $limit = 10, int $page = 1, array $facets = []): array;

    public function createIndex(string $indexName): void;

    public function updateSettings(string $indexName, array $indexSettings);
}