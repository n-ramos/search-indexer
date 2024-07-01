<?php

namespace Nramos\SearchIndexer\Meilisearch;

use Exception;
use JsonException;
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

    public function get(string $endpoint): array
    {
        return $this->api($endpoint, [], 'GET');
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->api($endpoint, $data, 'POST');
    }

    public function put(string $index, array $data = []): array
    {
        return $this->api('indexes/'.$index.'/documents', $data, 'PUT');
    }

    public function patch(string $endpoint, array $data = []): array
    {
        return $this->api($endpoint, $data, 'PATCH');
    }

    public function delete(string $index, int $id): array
    {
        return $this->api($index, ['id'], 'DELETE');
    }

    public function clear(string $index): array
    {
        return $this->api('indexes/'.$index.'/documents', [], 'DELETE');
    }

    public function search(string $indexName, string $query, ?SearchFilterInterface $filters = null, int $limit = 10, int $page = 1, array $facets = []): array
    {
        $dataToSend = [
            'q' => $query,
            'facets' => $facets,
            'limit' => $limit,
            'page' => $page,
        ];
        if ($filters instanceof \Nramos\SearchIndexer\Filter\SearchFilterInterface) {
            $dataToSend['filter'] = $filters->toString();
        }

        return $this->api('indexes/'.$indexName.'/search', $dataToSend);
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
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function api(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        $headers = [];
        if ($this->apiKey !== '' && $this->apiKey !== '0') {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        $response = $this->client->request($method, sprintf('http://%s/%s', $this->host, $endpoint), [
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
