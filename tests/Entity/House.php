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
    private $name;

    #[ORM\Column(type: 'integer')]
    #[MapProperty(propertyName: 'name', filterable: false, sortable: true)]
    private $price;

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
}