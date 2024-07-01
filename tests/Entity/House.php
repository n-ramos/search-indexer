<?php

namespace Nramos\SearchIndexer\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nramos\SearchIndexer\Annotation\Map;
use Nramos\SearchIndexer\Annotation\MapProperty;

#[ORM\Entity]
#[ORM\Table(name: 'houses')]
#[Map(indexName: 'houses', autoIndex: true)]
class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    #[MapProperty(propertyName: 'name', filterable: true, sortable: false)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer')]
    #[MapProperty(propertyName: 'price', filterable: false, sortable: true)]
    private $price;

    #[ORM\ManyToOne(targetEntity: HouseType::class)]
    #[ORM\JoinColumn(name: 'house_type_id', referencedColumnName: 'id')]
    #[MapProperty(propertyName: 'type', relationProperties: ['typeName'], filterable: true, sortable: false)]
    private $houseType;

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
