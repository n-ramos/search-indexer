<?php

namespace Nramos\SearchIndexer\Dto;

class SearchResultCollectionDto
{
    public function __construct(
        private MetaResultDto $meta,
        /** @var array<SearchResultSingleDto> $results */
        private array $results
    ) {
    }

    public function getMeta(): MetaResultDto
    {
        return $this->meta;
    }

    public function setMeta(MetaResultDto $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function getResults(): array
    {
        return $this->results;

    }

    public function setResults(array $results): self
    {
        $this->results = $results;
        return $this;
    }

    public function toArray(): array {
        $hits = array_map(function (SearchResultSingleDto $hit) {
            return $hit->toArray();
        }, $this->results);
        return [
            'meta' => $this->meta->toArray(),
            'results' => $hits
        ];
    }

    public static function transform(array $meta, array $hits): self {
        return new self(
            MetaResultDto::transform($meta),
            $hits
        );
    }
}