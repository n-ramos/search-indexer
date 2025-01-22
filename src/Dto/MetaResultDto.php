<?php

namespace Nramos\SearchIndexer\Dto;

class MetaResultDto
{
    public function __construct(
        private ?string $indexName,
        private ?float $score,
        private ?string $query,
        private ?int $limit,
        private ?int $offset,
        private ?int $perPage,
        private ?int $page,
        private ?int $totalPages,
        private ?int $totalHits,
        private ?int $estimatedHits,

    ){}

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): void
    {
        $this->indexName = $indexName;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function setQuery(?string $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    public function setPerPage(?int $perPage): self
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function getTotalPages(): ?int
    {
        return $this->totalPages;
    }

    public function setTotalPages(?int $totalPages): self
    {
        $this->totalPages = $totalPages;
        return $this;
    }

    public function getTotalHits(): ?int
    {
        return $this->totalHits;
    }

    public function setTotalHits(?int $totalHits): void
    {
        $this->totalHits = $totalHits;
    }

    public function getEstimatedHits(): ?int
    {
        return $this->estimatedHits;
    }

    public function setEstimatedHits(?int $estimatedHits): void
    {
        $this->estimatedHits = $estimatedHits;
    }
    public static function transform(array $data) : self {
        return new self(
            $data['indexName'],
            $data['score'],
            $data['query'],
            $data['limit'],
            $data['offset'],
            $data['perPage'],
            $data['page'],
            $data['totalPages'],
            $data['totalHits'],
            $data['estimatedHits'],
        );
    }
    public function toArray() : array {
        $result =  [
            'indexName' => $this->indexName,
            'score' => $this->score,
            'query' => $this->query,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'perPage' => $this->perPage,
            'page' => $this->page,
            'totalPages' => $this->totalPages,
            'totalHits' => $this->totalHits,
            'estimatedHits' => $this->estimatedHits,
        ];
        return array_filter($result, fn($value) => $value !== null);
    }


}