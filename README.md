[![codecov](https://codecov.io/gh/n-ramos/search-indexer/branch/main/graph/badge.svg?token=VZOQ2C6STQ)](https://codecov.io/gh/n-ramos/search-indexer)

# nramos/search-indexer

Le package `nramos/search-indexer` permet d'indexer des entités Doctrine sur différents systèmes d'indexation via des annotations. Actuellement, un adaptateur pour Meilisearch est disponible, mais la structure permet d'ajouter d'autres systèmes d'indexation à l'avenir.

## Installation

Installez le package via Composer :

```bash
composer require nramos/search-indexer
```

## Configuration
### Déclarer l'index sur une entité
Utilisez l'annotation `#[SearchIndex]` sur une classe pour définir un index. Par exemple :
    
```php
#[SearchIndex(indexName: 'biens', autoIndex: true)]
```
- **indexName** : le nom de l'index sur le système d'indexation.
- **autoIndex** : définit si les entités doivent être automatiquement indexées lors des insertions et des mises à jour. Si false, vous devrez gérer l'indexation manuellement.
### Configurer les propriétés à indexer
Utilisez l'annotation `#[SearchProperty]` sur les propriétés de l'entité pour définir comment celles-ci seront indexées :

```php
#[SearchProperty(propertyName: 'typeBien', relationProperties: [], filterable: true, sortable: true, searchable: true)]
```
### Configurer les propriétés à indexer
- propertyName : nom de la propriété à indexer.
- filterable : si la propriété peut être utilisée dans les filtres de recherche.
- sortable : si la propriété peut être utilisée pour trier les résultats.
- searchable : si la propriété peut être utilisée dans la recherche textuelle.
 -relationProperties : spécifie les clés à extraire dans le cas d'une relation (par ex. ManyToMany).

### Gérer les relations
Lorsque vous avez des relations comme ManyToMany, vous pouvez spécifier des relationProperties pour indexer des valeurs provenant de la relation. Par exemple :

```php
#[ORM\ManyToMany(targetEntity: Heating::class, mappedBy: 'houses')]
#[SearchProperty(propertyName: 'heatings', relationProperties: ['name'], filterable: true)]
private Collection $heatings;
```

Dans cet exemple, la propriété name des entités liées à Heating sera indexée sous le nom `heatings`.

### Désactiver l'auto-indexation
Si vous ne souhaitez pas utiliser l'indexation automatique fournie par le package, vous pouvez désactiver l'auto-indexation en définissant `autoIndex: false` dans l'annotation `#[SearchIndex]` et en créant un subscriber personnalisé pour gérer l'indexation.

## Utilisation des filtres de recherche
Le package propose une interface SearchFilterInterface pour faciliter la création de requêtes complexes. Un adaptateur pour Meilisearch est disponible par défaut. Voici un exemple d'utilisation avec Meilisearch :

```php
$filter = (new MeiliSearchFilter())
    ->addFilter('status', '=', 'active')
    ->addFilter('rating.users', '>', 85)
    ->openParenthesis()
    ->addFilter('genres', '=', 'horror', 'OR')
    ->addFilter('genres', '=', 'comedy')
    ->closeParenthesis()
    ->openParenthesis()
    ->addFilter('genres', '=', 'horror')
    ->addFilter('genres', '=', 'comedy')
    ->closeParenthesis()
    ->addInFilter('role', ['admin', 'user'])
    ->addLocationFilter('radius', 48.8566, 2.3522, 5, 'km')
    ->addLocationBounding('bounding', [48.8566, 2.3522, 49.8566, 2.4522], 'km')
    ->addExistenceFilter('release_date')
    ->addExistenceFilter('overview', false);

```

### Exécution de la recherche
Pour exécuter la recherche, vous devez implémenter l'interface SearchClientInterface. Un client Meilisearch est fourni par le package sous la classe `MeilisearchClient`. Voici un exemple :

```php
$results = $client->search(
    'houses', // Nom de l'index
    'search query', // Requête de recherche
    $filter, // Filtre de recherche
    10, // Limite
    1, // Page
    ['status', 'genres'] // Facettes
);

```

## Exemple d'entité
Voici un exemple d'entité avec les annotations pour l'indexation :
```php
namespace App\Entity;

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
```

## Conclusion
Le package nramos/search-indexer vous permet d'indexer vos entités de manière flexible et automatique via des annotations. Il supporte actuellement Meilisearch, mais peut être étendu pour d'autres systèmes d'indexation.
