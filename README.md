[![codecov](https://codecov.io/gh/n-ramos/search-indexer/branch/main/graph/badge.svg?token=VZOQ2C6STQ)](https://codecov.io/gh/n-ramos/search-indexer)
🌍 Documentation

This documentation is available in:
#### 🇬🇧 English
#### 🇫🇷 Français

# 🇫🇷 Français

Le package `nramos/search-indexer` permet d'indexer des entités Doctrine sur différents systèmes d'indexation via des annotations. Actuellement, un adaptateur pour Meilisearch est disponible, mais la structure permet d'ajouter d'autres systèmes d'indexation à l'avenir.

## Installation

Installez le package via Composer :

```bash
composer require nramos/search-indexer
```

## Configuration
### Déclarer le service dans services.yaml:
Exemple pour Meilisearch:
```yaml
parameters:
    meilisearch_default_host: "localhost:7700"
    empty: ""
    meilisearch_key:    '%env(default:empty:MEILISEARCH_KEY)%'
    meilisearch_host:   '%env(default:meilisearch_default_host:MEILISEARCH_HOST)%'
services:
  ...
 Nramos\SearchIndexer\Indexer\SearchClientInterface:
  class: Nramos\SearchIndexer\Meilisearch\MeilisearchClient
  bind:
   $host: '%meilisearch_host%'
   $apiKey: '%meilisearch_key%'

```
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
Il faut obligatoirement ajouter une clé primaire à l'indexer : 
```php
#[SearchProperty(propertyName: 'id', isPk:true, relationProperties: [], filterable: true, sortable: true, searchable: true)]
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
    #[SearchProperty(propertyName: 'id',isPk: true, filterable: true, sortable: false)]
  
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
    #[SearchProperty(propertyName: 'houseTypeFormated', filterable: true, sortable: false)]
    public function getHouseTypeFormated(): mixed
    {
        return $this->houseType " - de 50 m²";
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

## Aller plus loin
### Créer un subscriber personnalisé
Pour gérer l'indexation manuellement, vous pouvez créer un subscriber personnalisé pour écouter les événements Doctrine et appeler l'indexation manuellement. Voici un exemple implémenté globalement :
```php 
#[SearchIndex(indexName: 'houses', autoIndex: false)]
```
```php
<?php

namespace Nramos\SearchIndexer\Indexer;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @see SearchIndexerSubscriberTest
 */
#[AsDoctrineListener(event: Events::postPersist, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::preRemove, priority: 0, connection: 'default')]
class SearchIndexerSubscriber
{
    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly LoggerInterface $logger
    )
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove,
        ];
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        try {
            $this->indexEntity($args);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof IndexableEntityInterface) {
            return;
        }

        $reflectionClass = new ReflectionClass($entity);

        if ($reflectionClass->getAttributes(SearchIndex::class) && $reflectionClass->getAttributes(SearchIndex::class)[0]->newInstance()->autoIndex) {
            try {
                $this->indexer->remove($entity);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

        }
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        try {
            $this->indexEntity($args);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    private function indexEntity(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof IndexableEntityInterface) {
            return;
        }

        $reflectionClass = new ReflectionClass($entity);

        if ($reflectionClass->getAttributes(SearchIndex::class) && $reflectionClass->getAttributes(SearchIndex::class)[0]->newInstance()->autoIndex) {
            try {
                $this->indexer->index($entity);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

        }
    }
}
```

### Effectuer une multi recherche
Pour effectuer une recherche multi-index, vous pouvez utiliser la méthode `searchMulti` du client. Voici un exemple :

```php
use Nramos\SearchIndexer\Indexer\IndexableObjects;
use Nramos\SearchIndexer\Indexer\SearchClientInterface;
class MultiSearchService
{

    public function __construct(
        private SearchClientInterface $client,
        private IndexableObjects $indexableObjects,
      
    ) {}
    
    public function searchAcrossIndexes(string $query, array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $multiSearchQueries = [];
        $indexableMap = [];
        $indexedClasses = $this->indexableObjects->getIndexedClasses();

        foreach ($indexedClasses as $className) {
            if (!class_exists($className)) {
                throw new \InvalidArgumentException(\sprintf("La classe %s n'existe pas.", $className));
            }
                $multiSearchQueries[] =  [
                    'indexUid' => $indexName,
                    'q' => $query,
                    'facets' => [],
                ];
                $indexableMap[$indexName] = $indexable;
            
        }

        return $this->client->multiSearch($multiSearchQueries);
       
    }
```
### Créer un adaptateur pour un autre système d'indexation
Vous pouvez vous baser sur la class MeilisearchClient. Veillez à bien changer le services dans votre fichier services.yaml: 

```yaml
services:
  ...
   Nramos\SearchIndexer\Indexer\SearchClientInterface:
    class: Nramos\SearchIndexer\Meilisearch\MeilisearchClient
    bind:
     $host: '%meilisearch_host%'
     $apiKey: '%meilisearch_key%'
```
## Conclusion
Le package nramos/search-indexer vous permet d'indexer vos entités de manière flexible et automatique via des annotations. Il supporte actuellement Meilisearch, mais peut être étendu pour d'autres systèmes d'indexation.


# 🇬🇧 English

The `nramos/search-indexer` package allows indexing Doctrine entities on different indexing systems via annotations. Currently, an adapter for Meilisearch is available, but the structure allows adding other indexing systems in the future.

## Installation

Install the package via Composer:

```bash
composer require nramos/search-indexer
```

## Configuration

### Declare the service in `services.yaml`:

Example for Meilisearch:

```yaml
parameters:
    meilisearch_default_host: "localhost:7700"
    empty: ""
    meilisearch_key:    '%env(default:empty:MEILISEARCH_KEY)%'
    meilisearch_host:   '%env(default:meilisearch_default_host:MEILISEARCH_HOST)%'
services:
  ...
 Nramos\SearchIndexer\Indexer\SearchClientInterface:
  class: Nramos\SearchIndexer\Meilisearch\MeilisearchClient
  bind:
   $host: '%meilisearch_host%'
   $apiKey: '%meilisearch_key%'
```

### Declare the index on an entity

Use the `#[SearchIndex]` annotation on a class to define an index. For example:

```php
#[SearchIndex(indexName: 'biens', autoIndex: true)]
```

- **indexName**: The name of the index in the indexing system.
- **autoIndex**: Defines whether entities should be automatically indexed on inserts and updates. If false, you will need to manage indexing manually.

### Configure the properties to index

Use the `#[SearchProperty]` annotation on entity properties to define how they will be indexed:

```php
#[SearchProperty(propertyName: 'typeBien', relationProperties: [], filterable: true, sortable: true, searchable: true)]
```

You must add a primary key to the index:

```php
#[SearchProperty(propertyName: 'id', isPk:true, relationProperties: [], filterable: true, sortable: true, searchable: true)]
```

### Configure indexed properties

- **propertyName**: Name of the property to index.
- **filterable**: Whether the property can be used in search filters.
- **sortable**: Whether the property can be used to sort results.
- **searchable**: Whether the property can be used in text search.
- **relationProperties**: Specifies keys to extract in case of a relation (e.g., ManyToMany).

### Handling relations

For relations like ManyToMany, you can specify `relationProperties` to index values from the related entity. Example:

```php
#[ORM\ManyToMany(targetEntity: Heating::class, mappedBy: 'houses')]
#[SearchProperty(propertyName: 'heatings', relationProperties: ['name'], filterable: true)]
private Collection $heatings;
```

In this example, the `name` property of related `Heating` entities will be indexed under `heatings`.

### Disable auto-indexing

If you don't want to use the package's automatic indexing, you can disable it by setting `autoIndex: false` in the `#[SearchIndex]` annotation and creating a custom subscriber to handle indexing manually.

## Using search filters

The package provides a `SearchFilterInterface` to simplify creating complex queries. A Meilisearch adapter is available by default. Here is an example usage with Meilisearch:

```php
$filter = (new MeiliSearchFilter())
    ->addFilter('status', '=', 'active')
    ->addFilter('rating.users', '>', 85)
    ->addInFilter('role', ['admin', 'user'])
    ->addLocationFilter('radius', 48.8566, 2.3522, 5, 'km');
```

### Executing a search

To execute a search, you need to implement the `SearchClientInterface`. A Meilisearch client is provided in the package under `MeilisearchClient`. Example:

```php
$results = $client->search(
    'houses', // Index name
    'search query', // Search query
    $filter, // Search filter
    10, // Limit
    1, // Page
    ['status', 'genres'] // Facets
);
```

## Example entity

Here is an example entity with indexing annotations:

```php
#[ORM\Entity]
#[ORM\Table(name: 'houses')]
#[SearchIndex(indexName: 'houses', autoIndex: true)]
class House implements IndexableEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    #[SearchProperty(propertyName: 'id', isPk: true, filterable: true, sortable: false)]
    private $id;

    #[ORM\Column(type: 'string')]
    #[SearchProperty(propertyName: 'name', filterable: true, sortable: false)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer')]
    #[SearchProperty(propertyName: 'price', filterable: false, sortable: true)]
    private $price;
}
```

## Going further

### Create a custom subscriber

To manually manage indexing, create a custom subscriber to listen to Doctrine events and trigger indexing manually. Example:

```php
#[SearchIndex(indexName: 'houses', autoIndex: false)]
```

```php
#[AsDoctrineListener(event: Events::postPersist, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 0, connection: 'default')]
#[AsDoctrineListener(event: Events::preRemove, priority: 0, connection: 'default')]
class SearchIndexerSubscriber
{
    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly LoggerInterface $logger
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->indexEntity($args);
    }
}
```

### Perform multi-index search

Use the `searchMulti` method to search across multiple indexes:

```php
public function searchAcrossIndexes(string $query, array $filters = [], int $limit = 100, int $offset = 0): array
{
    return $this->client->multiSearch($multiSearchQueries);
}
```

### Create an adapter for another indexing system

Use the `MeilisearchClient` class as a reference and update `services.yaml`:

```yaml
services:
  ...
   Nramos\SearchIndexer\Indexer\SearchClientInterface:
    class: YourCustomIndexerClient
```

## Conclusion

The `nramos/search-indexer` package allows flexible and automatic entity indexing via annotations. It currently supports Meilisearch but can be extended to other indexing systems.

