<?php
// src/Infrastructure/Search/GenericIndexer.php
namespace Nramos\SearchIndexer\Indexer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Nramos\SearchIndexer\Annotation\IndexCondition;
use Nramos\SearchIndexer\Annotation\IndexConditionInterface;
use Nramos\SearchIndexer\Annotation\Map;
use Nramos\SearchIndexer\Annotation\MapProperty;
use ReflectionClass;

class GenericIndexer implements IndexerInterface
{
    private array $indexSettings = [];
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SearchClientInterface $client
    ) {
    }

    public function index(array $data): void
    {
        $entityClass = $data['entityClass'];
        $entity = $this->em->getRepository($entityClass)->find($data['id']);

        if ($entity && $this->shouldIndexEntity($entity)) {
            $indexName = $this->getIndexName($entityClass);
            $indexData = $this->extractData($entity);
            $this->client->put($indexName, [$indexData]);

            $this->updateIndexSettings($entityClass);
        }
    }

    public function remove(string $id, string $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass);
        $this->client->delete($indexName, $id);
    }

    public function clean(string $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass);
        $this->client->clear($indexName);
    }

    private function extractData($entity): array
    {
        $reflectionClass = new ReflectionClass($entity);
        $data = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(MapProperty::class);
            if ($attributes) {
                $property->setAccessible(true);
                $annotation = $attributes[0]->newInstance();
                $value = $property->getValue($entity);

                // Désinitialisation du proxy si nécessaire
                if ($value instanceof Proxy) {
                    $this->em->initializeObject($value);
                }

                if (!empty($annotation->relationProperties) && $value) {
                    if (is_iterable($value)) {
                        $data[$annotation->propertyName] = array_map(
                            fn($relatedEntity) => $this->getRelationPropertiesValue($relatedEntity, $annotation->relationProperties),
                            $value->toArray()
                        );
                    } else {
                        $relationValues = $this->getRelationPropertiesValue($value, $annotation->relationProperties);
                        if (count($relationValues) === 1) {
                            $data[$annotation->propertyName] = reset($relationValues);
                        } else {
                            $data[$annotation->propertyName] = $relationValues;
                        }
                    }
                } else {
                    $data[$annotation->propertyName] = $value;
                }
                $this->addIndexSettings($annotation, $annotation->propertyName);
            }
        }
        return $data;
    }

    private function getRelationPropertiesValue($entity, array $propertyNames): array
    {
        // Initialiser le proxy si nécessaire
        if ($entity instanceof Proxy) {
            $this->em->initializeObject($entity);
        }

        $reflectionClass = new ReflectionClass($this->em->getClassMetadata($entity::class)->name);
        $values = [];
        foreach ($propertyNames as $propertyName) {

            $property = $reflectionClass->getProperty($propertyName);

            $property->setAccessible(true);
            $values[$propertyName] = $property->getValue($entity);
        }
        return $values;
    }

    private function getIndexName(string $entityClass): string
    {
        $reflectionClass = new ReflectionClass($entityClass);
        $attributes = $reflectionClass->getAttributes(Map::class);

        if (!$attributes) {
            throw new \Exception("Entity class {$entityClass} is not mapped to an index.");
        }

        $annotation = $attributes[0]->newInstance();
        return $annotation->indexName;
    }

    private function shouldIndexEntity(object $entity): bool
    {
        $reflectionClass = new ReflectionClass($entity);
        $attributes = $reflectionClass->getAttributes(IndexCondition::class);

        if ($attributes) {
            $annotation = $attributes[0]->newInstance();
            $conditionClass = $annotation->conditionClass;
            if ($conditionClass) {
                $condition = new $conditionClass();
                if (!$condition instanceof IndexConditionInterface) {
                    throw new \Exception("Condition class {$conditionClass} must implement IndexConditionInterface.");
                }
                return $condition($entity);
            }
        }

        return true; // Par défaut, on indexe l'entité si aucune condition n'est spécifiée
    }
    private function addIndexSettings(MapProperty $annotation, string $propertyName): void
    {
        if ($annotation->filterable) {
            $this->indexSettings['filterable'][] = $propertyName;
        }
        if ($annotation->sortable) {
            $this->indexSettings['sortable'][] = $propertyName;
        }
        if ($annotation->searchable) {
            $this->indexSettings['searchable'][] = $propertyName;
        }
    }

    private function updateIndexSettings(string $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass);

        // Mettre à jour les paramètres de l'index sur le client de recherche
        if (!empty($this->indexSettings)) {
            $this->client->updateSettings($indexName, $this->indexSettings);
        }

        // Réinitialiser les paramètres de l'index pour la prochaine utilisation
        $this->indexSettings = [];
    }
}

