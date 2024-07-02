<?php

namespace Nramos\SearchIndexer\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Nramos\SearchIndexer\Annotation\SearchProperty;
use Nramos\SearchIndexer\Indexer\IndexableEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'houses')]
#[SearchIndex(indexName: 'houses', autoIndex: true)]
class House implements IndexableEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    #[SearchProperty(propertyName: 'name', filterable: true, sortable: false)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer')]
    #[SearchProperty(propertyName: 'price', filterable: false, sortable: true)]
    private $price;

    #[ORM\ManyToOne(targetEntity: HouseType::class)]
    #[ORM\JoinColumn(name: 'house_type_id', referencedColumnName: 'id')]
    #[SearchProperty(propertyName: 'type', relationProperties: ['typeName'], filterable: true, sortable: false)]
    private $houseType;

    #[ORM\ManyToMany(targetEntity: Heating::class, mappedBy: 'houses')]
    #[SearchProperty(propertyName: 'heatings', relationProperties: ['name'], filterable: true)]
    private Collection $heatings;

    public function __construct()
    {
        $this->heatings = new ArrayCollection();
    }

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

    public function getPrice(): mixed
    {
        return $this->price;
    }

    public function setPrice(mixed $price): void
    {
        $this->price = $price;
    }

    public function getHouseType(): mixed
    {
        return $this->houseType;
    }

    public function setHouseType(mixed $houseType): void
    {
        $this->houseType = $houseType;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }
}
