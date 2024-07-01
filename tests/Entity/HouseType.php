<?php

namespace Nramos\SearchIndexer\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'house_types')]
class HouseType
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    private $typeName;

    #[ORM\OneToMany(targetEntity: House::class, mappedBy: 'houseType')]
    private $houses;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    public function setTypeName(string $typeName): self
    {
        $this->typeName = $typeName;

        return $this;
    }

    public function getHouses(): mixed
    {
        return $this->houses;
    }

    public function setHouses(mixed $houses): void
    {
        $this->houses = $houses;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }
}
