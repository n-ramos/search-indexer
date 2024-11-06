<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Nramos\SearchIndexer\Annotation\SearchProperty;
use Nramos\SearchIndexer\Indexer\IndexableEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
#[SearchIndex(indexName: 'posts', autoIndex: false)]
class Post implements IndexableEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    #[SearchProperty(propertyName: 'name', filterable: true, sortable: false)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }
}
