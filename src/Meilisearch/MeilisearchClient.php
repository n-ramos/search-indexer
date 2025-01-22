<?php

namespace Nramos\SearchIndexer\Meilisearch;

use Exception;
use JsonException;
use Nramos\SearchIndexer\Dto\MetaResultDto;
use Nramos\SearchIndexer\Dto\SearchResultCollectionDto;
use Nramos\SearchIndexer\Dto\SearchResultSingleDto;
use Nramos\SearchIndexer\Filter\SearchFilterInterface;
use Nramos\SearchIndexer\Indexer\SearchClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeilisearchClient implements SearchClientInterface
{
    public function __construct(private readonly string $host, private readonly string $apiKey, private readonly HttpClientInterface $client) {}

    public function get(string $endpoint): mixed
    {
        return $this->api($endpoint, [], 'GET');
    }

    public function post(string $endpoint, array $data = []): mixed
    {
        return $this->api($endpoint, $data, 'POST');
    }

    public function put(string $index, array $data = []): mixed
    {
        return $this->api('indexes/'.$index.'/documents', $data, 'PUT');
    }

    public function patch(string $endpoint, array $data = []): mixed
    {
        return $this->api($endpoint, $data, 'PATCH');
    }

    public function delete(string $index): mixed
    {
        return $this->api('indexes/'.$index, [], 'DELETE');
    }

    public function clear(string $index): mixed
    {
        return $this->api('indexes/'.$index.'/documents', [], 'DELETE');
    }

    public function search(string $indexName, string $query, ?SearchFilterInterface $filters = null, int $limit = 10, int $page = 1, array $facets = []): SearchResultCollectionDto
    {
        $dataToSend = [
            'q' => $query,
            'facets' => $facets,
            'limit' => $limit,
            'page' => $page,
        ];
        if ($filters instanceof SearchFilterInterface) {
            $dataToSend['filter'] = $filters->toString();
        }
        $results = $this->api('indexes/'.$indexName.'/search', $dataToSend);

        $hits = array_map(function (array $hit) use ($indexName) {
            $meta = MetaResultDto::transform($this->formatMeta($hit));
            $meta->setIndexName($indexName);
            return (SearchResultSingleDto::transform($hit, $meta));
        }, $results['hits']);

        return SearchResultCollectionDto::transform($this->formatMeta($results), $hits);
    }
    private function formatMeta(array $data): array
    {
        return [
            'indexName' => $data['indexName'] ?? null,
            'query' => $data['query'] ?? null,
            'limit' => $data['limit'] ?? null,
            'offset' => $data['offset'] ?? null,
            'estimatedHits' => $data['estimatedTotalHits'] ?? null,
            'page' => $data['page'] ?? null,
            'perPage' => $data['hitsPerPage'] ?? null,
            'score' => $data['_rankingScore'] ?? null,
            'totalPages' => $data['totalPages'] ?? null,
            'totalHits' => $data['totalHits'] ?? null,
        ];
    }
    public function createIndex(string $indexName): void
    {
        $this->api('indexes', [
            'primaryKey' => 'id',
            'uid' => $indexName,
        ]);
    }

    public function updateSettings(string $indexName, array $indexSettings): void
    {
        $searchableAttributes = $indexSettings['searchable'] ?? [];
        $filterablesAttributes = $indexSettings['filterable'] ?? [];
        $sortableAttributes = $indexSettings['sortable'] ?? [];

        $this->api('indexes/'.$indexName.'/settings', [
            'searchableAttributes' => $searchableAttributes,
            'sortableAttributes' => $sortableAttributes,
            'filterableAttributes' => $filterablesAttributes,
        ], 'PATCH');

        if (isset($indexSettings['primaryKey'])) {
            $this->api('indexes/'.$indexName, [
                'primaryKey' => $indexSettings['primaryKey'],
            ], 'PATCH');
        }
    }

    public function multiSearch(array $queries, int $limit = 100, int $offset = 0): SearchResultCollectionDto
    {
        // Meilisearch multi-search requiert l'envoi des requêtes dans un tableau 'queries'
        $dataToSend = [
            'federation' => [
                'offset' => $offset,
                'limit' => $limit,
            ],
            'queries' => $queries,
        ];
        $results = $this->api('multi-search', $dataToSend);
        $hits = array_map(function (array $hit) {
            $meta = MetaResultDto::transform($this->formatMeta($hit));
            $meta->setScore($hit['_federation']['weightedRankingScore'] ?? null);
            $meta->setIndexName($hit['_federation']['indexUid'] ?? null);
            unset($hit['_federation']);
            return (SearchResultSingleDto::transform($hit, $meta));
        }, $results['hits']);

        return SearchResultCollectionDto::transform($this->formatMeta($results), $hits);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function api(string $endpoint, array $data = [], string $method = 'POST'): mixed
    {
        $headers = [];
        if ('' !== $this->apiKey && '0' !== $this->apiKey) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        $response = $this->client->request($method, \sprintf('http://%s/%s', $this->host, $endpoint), [
            'json' => $data,
            'headers' => $headers,
        ]);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        }

        $result = $response->getContent(false);
        json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        throw new Exception($result);
    }
}
