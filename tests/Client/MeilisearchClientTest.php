<?php

namespace Nramos\SearchIndexer\Tests\Client;

use Exception;
use JsonException;
use Nramos\SearchIndexer\Filter\SearchFilterInterface;
use Nramos\SearchIndexer\Meilisearch\MeilisearchClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[Small] final class MeilisearchClientTest extends TestCase
{
    private $httpClient;
    private $meilisearchClient;
    private $host = 'localhost';
    private $apiKey = 'test_api_key';

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->meilisearchClient = new MeilisearchClient($this->host, $this->apiKey, $this->httpClient);
    }

    public function testGet()
    {
        $this->mockHttpClient('GET', 'test_endpoint', [], 200, ['response' => 'data']);
        $response = $this->meilisearchClient->get('test_endpoint');
        self::assertSame(['response' => 'data'], $response);
    }

    public function testPost()
    {
        $data = ['key' => 'value'];
        $this->mockHttpClient('POST', 'test_endpoint', $data, 200, ['response' => 'data']);
        $response = $this->meilisearchClient->post('test_endpoint', $data);
        self::assertSame(['response' => 'data'], $response);
    }

    public function testPut()
    {
        $data = ['key' => 'value'];
        $this->mockHttpClient('PUT', 'indexes/test_index/documents', $data, 200, ['response' => 'data']);
        $response = $this->meilisearchClient->put('test_index', $data);
        self::assertSame(['response' => 'data'], $response);
    }

    public function testPatch()
    {
        $data = ['key' => 'value'];
        $this->mockHttpClient('PATCH', 'test_endpoint', $data, 200, ['response' => 'data']);
        $response = $this->meilisearchClient->patch('test_endpoint', $data);
        self::assertSame(['response' => 'data'], $response);
    }

    public function testDelete()
    {
        $this->mockHttpClient('DELETE', 'test_index', ['id'], 200, ['response' => 'data']);
        $response = $this->meilisearchClient->delete('test_index', 1);
        self::assertSame(['response' => 'data'], $response);
    }

    public function testClear()
    {
        $this->mockHttpClient('DELETE', 'indexes/test_index/documents', [], 200, ['response' => 'data']);
        $response = $this->meilisearchClient->clear('test_index');
        self::assertSame(['response' => 'data'], $response);
    }

    public function testSearch()
    {
        $filters = $this->createMock(SearchFilterInterface::class);
        $filters->method('toString')->willReturn('filter_string');

        $expectedData = [
            'q' => 'query',
            'facets' => [],
            'limit' => 10,
            'page' => 1,
            'filter' => 'filter_string',
        ];

        $this->mockHttpClient('POST', 'indexes/test_index/search', $expectedData, 200, ['response' => 'data']);

        $response = $this->meilisearchClient->search('test_index', 'query', $filters);
        self::assertSame(['response' => 'data'], $response);
    }

    public function testCreateIndex()
    {
        $data = ['primaryKey' => 'id', 'uid' => 'test_index'];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode(null));

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'http://'.$this->host.'/indexes',
                [
                    'json' => $data,
                    'headers' => ['Authorization' => 'Bearer '.$this->apiKey],
                ]
            )
            ->willReturn($response)
        ;

        try {
            $this->meilisearchClient->createIndex('test_index');
            self::assertTrue(true); // Si aucune exception n'est levée, le test réussit
        } catch (Exception $e) {
            self::fail('Exception raised: '.$e->getMessage());
        }
    }

    public function testUpdateSettings()
    {
        $indexSettings = [
            'searchable' => ['attribute1'],
            'filterable' => ['attribute2'],
            'sortable' => ['attribute3'],
        ];

        $expectedData = [
            'searchableAttributes' => ['attribute1'],
            'sortableAttributes' => ['attribute3'],
            'filterableAttributes' => ['attribute2'],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode(null));

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(
                'PATCH',
                'http://'.$this->host.'/indexes/test_index/settings',
                [
                    'json' => $expectedData,
                    'headers' => ['Authorization' => 'Bearer '.$this->apiKey],
                ]
            )
            ->willReturn($response)
        ;

        try {
            $this->meilisearchClient->updateSettings('test_index', $indexSettings);
            self::assertTrue(true); // Si aucune exception n'est levée, le test réussit
        } catch (Exception $e) {
            self::fail('Exception raised: '.$e->getMessage());
        }
    }

    #[DataProvider('errorCases')]
    public function testApiErrors($statusCode, $exception)
    {
        $this->expectException($exception);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getContent')->willReturn('error content');

        $this->httpClient->method('request')->willReturn($response);

        $this->meilisearchClient->get('test_endpoint');
    }

    public static function errorCases(): array
    {
        return [
            [500, JsonException::class],
        ];
    }

    private function mockHttpClient($method, $endpoint, $data, $statusCode, $responseContent): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getContent')->willReturn(json_encode($responseContent));

        $this->httpClient->method('request')
            ->with($method, 'http://'.$this->host.'/'.$endpoint, [
                'json' => $data,
                'headers' => ['Authorization' => 'Bearer '.$this->apiKey],
            ])
            ->willReturn($response)
        ;
    }
}
