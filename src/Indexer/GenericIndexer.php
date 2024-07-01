<?php

namespace Nramos\SearchIndexer\Indexer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Exception;
use InvalidArgumentException;
use Nramos\SearchIndexer\Annotation\IndexCondition;
use Nramos\SearchIndexer\Annotation\IndexConditionInterface;
use Nramos\SearchIndexer\Annotation\Map;
use Nramos\SearchIndexer\Annotation\MapProperty;
use ReflectionClass;
use ReflectionException;

class GenericIndexer implements IndexerInterface
{
    private array $indexSettings = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SearchClientInterface $client
    ) {}

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

    public function remove(int $id, string $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass);
        $this->client->delete($indexName, $id);
    }

    public function clean(string $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass);
        $this->client->clear($indexName);
    }

    /**
     * @throws ReflectionException
     */
    public function extractData(object $entity): array
    {
        if (!\is_object($entity)) {
            throw new InvalidArgumentException('The entity must be an object.');
        }

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
                        // Convertir l'iterable en tableau
                        $arrayValue = \is_array($value) ? $value : iterator_to_array($value);

                        $data[$annotation->propertyName] = array_map(
                            fn ($relatedEntity): array => $this->getRelationPropertiesValue($relatedEntity, $annotation->relationProperties),
                            $arrayValue
                        );
                    } else {
                        $relationValues = $this->getRelationPropertiesValue($value, $annotation->relationProperties);
                        $data[$annotation->propertyName] = 1 === \count($relationValues) ? reset($relationValues) : $relationValues;
                    }
                } else {
                    $data[$annotation->propertyName] = $value;
                }

                $this->addIndexSettings($annotation, $annotation->propertyName);
            }
        }

        return $data;
    }

    public function getRelationPropertiesValue(mixed $entity, array $propertyNames): array
    {
        if (!\is_object($entity)) {
            throw new InvalidArgumentException('The entity must be an object.');
        }

        // Initialiser le proxy si nécessaire
        if ($entity instanceof Proxy) {
            $this->em->initializeObject($entity);
        }

        $reflectionClass = new ReflectionClass($entity);
        $values = [];
        foreach ($propertyNames as $propertyName) {
            $property = $reflectionClass->getProperty($propertyName);

            $property->setAccessible(true);
            $values[$propertyName] = $property->getValue($entity);
        }

        return $values;
    }

    /**
     * @throws ReflectionException
     */
    public function getIndexName(string $entityClass): string
    {
        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException(sprintf('The class %s does not exist.', $entityClass));
        }

        $reflectionClass = new ReflectionClass($entityClass);
        $attributes = $reflectionClass->getAttributes(Map::class);

        if ([] === $attributes) {
            throw new Exception(sprintf('Entity class %s is not mapped to an index.', $entityClass));
        }

        $annotation = $attributes[0]->newInstance();

        return $annotation->indexName;
    }

    public function updateIndexSettings(string $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass);

        // Mettre à jour les paramètres de l'index sur le client de recherche
        if ([] !== $this->indexSettings) {
            $this->client->updateSettings($indexName, $this->indexSettings);
        }

        // Réinitialiser les paramètres de l'index pour la prochaine utilisation
        $this->indexSettings = [];
    }

    private function shouldIndexEntity(object $entity): bool
    {
        if (!\is_object($entity)) {
            throw new InvalidArgumentException('The entity must be an object.');
        }

        $reflectionClass = new ReflectionClass($entity);
        $attributes = $reflectionClass->getAttributes(IndexCondition::class);

        if ([] !== $attributes) {
            $annotation = $attributes[0]->newInstance();
            $conditionClass = $annotation->conditionClass;
            if ($conditionClass) {
                if (!class_exists($conditionClass)) {
                    throw new InvalidArgumentException(sprintf('The condition class %s does not exist.', $conditionClass));
                }

                $condition = new $conditionClass();
                if (!$condition instanceof IndexConditionInterface) {
                    throw new Exception(sprintf('Condition class %s must implement IndexConditionInterface.', $conditionClass));
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
}
