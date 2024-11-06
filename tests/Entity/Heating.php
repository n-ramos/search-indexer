<?php

namespace Nramos\SearchIndexer\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nramos\SearchIndexer\Annotation\SearchProperty;

#[ORM\Entity]
#[ORM\Table(name: 'heatings')]
class Heating
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string')]
    #[SearchProperty(propertyName: 'name', filterable: true, sortable: false)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: House::class, mappedBy: 'heatings')]
    private Collection $houses;

    public function __construct()
    {
        $this->houses = new ArrayCollection();
    }

    public function getId(): mixed
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

    public function getHouses(): Collection
    {
        return $this->houses;
    }

    public function setHouses(Collection $houses): void
    {
        $this->houses = $houses;
    }
}
