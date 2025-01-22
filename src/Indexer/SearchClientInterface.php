<?php

namespace Nramos\SearchIndexer\Indexer;

use Nramos\SearchIndexer\Dto\SearchResultCollectionDto;
use Nramos\SearchIndexer\Filter\SearchFilterInterface;

interface SearchClientInterface
{
    public function put(string $index, array $data = []): mixed;

    public function delete(string $index): mixed;

    public function clear(string $index): mixed;

    public function search(string $indexName, string $query, ?SearchFilterInterface $filters = null, int $limit = 10, int $page = 1, array $facets = []): SearchResultCollectionDto;

    public function createIndex(string $indexName): void;

    public function updateSettings(string $indexName, array $indexSettings): void;

    public function multiSearch(array $queries): SearchResultCollectionDto;
}
