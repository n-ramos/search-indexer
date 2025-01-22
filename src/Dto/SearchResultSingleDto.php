<?php

namespace Nramos\SearchIndexer\Dto;

class SearchResultSingleDto
{

    public function __construct(
        private MetaResultDto $meta,
        private array $result = [],
    )
    {
    }

    public function toArray(): array
    {

        return array_merge($this->result, ['meta' => $this->meta->toArray()]);
    }

    public static function transform(array $hit, MetaResultDto $meta): self {
        return new self(
            $meta,
            $hit,
        );
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): self
    {
        $this->result = $result;
        return $this;
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

}