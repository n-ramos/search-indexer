<?php

namespace Entity;

use Nramos\SearchIndexer\Annotation\SearchIndex;
use Nramos\SearchIndexer\Indexer\IndexableEntityInterface;

#[SearchIndex(indexName: 'houses', autoIndex: false)]
class HouseDto implements IndexableEntityInterface
{
    private int $id;
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
