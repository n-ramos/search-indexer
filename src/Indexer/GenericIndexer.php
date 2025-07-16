<?php

namespace Nramos\SearchIndexer\Indexer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Exception;
use InvalidArgumentException;
use Nramos\SearchIndexer\Annotation\SearchIndex;
use Nramos\SearchIndexer\Annotation\SearchProperty;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class GenericIndexer implements IndexerInterface
{
    private array $indexSettings = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SearchClientInterface $client
    ) {}

    public function index(object $data): void
    {
        $className = $this->getRealClass($data);
        $indexName = $this->getIndexName($className);
        $indexData = $this->extractData($data);
        $this->client->put($indexName, [$indexData]);
    }

    public function getRealClass(object|string $subject): string
    {
        $subject = \is_object($subject) ? $subject::class : $subject;

        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        $positionCg = mb_strrpos($subject, '\__CG__\\');
        $positionPm = mb_strrpos($subject, '\__PM__\\');
        if (false === $positionCg && false === $positionPm) {
            return $subject;
        }

        if (false !== $positionCg) {
            return mb_substr($subject, $positionCg + 8);
        }

        $className = mb_ltrim($subject, '\\');

        return mb_substr(
            $className,
            8 + $positionPm,
            mb_strrpos($className, '\\') - ($positionPm + 8)
        );
    }

    public function remove(IndexableEntityInterface $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass::class);
        $this->client->delete($indexName);
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
            $this->handleProperties($property, $entity, $data);
        }
        foreach ($reflectionClass->getMethods() as $property) {
            $this->handleProperties($property, $entity, $data);
        }

        return $data;
    }

    /**
     * @throws ReflectionException
     */
    public function getRelationPropertiesValue(mixed $entity, array $propertyNames): array
    {
        if (!\is_object($entity)) {
            throw new InvalidArgumentException('The entity must be an object.');
        }

        $reflectionClass = new ReflectionClass($entity);
        if ($entity instanceof Proxy) {
            $reflectionClass = $reflectionClass->getParentClass();
        }
        $values = [];
        if (false === $reflectionClass) {
            return [];
        }
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
            throw new InvalidArgumentException(\sprintf('The class %s does not exist.', $entityClass));
        }

        $reflectionClass = new ReflectionClass($entityClass);
        $attributes = $reflectionClass->getAttributes(SearchIndex::class);

        if ([] === $attributes) {
            throw new Exception(\sprintf('Entity class %s is not mapped to an index.', $entityClass));
        }

        $annotation = $attributes[0]->newInstance();

        return $annotation->indexName;
    }

    public function updateIndexSettings(string $entityClass): void
    {
        $indexName = $this->getIndexName($entityClass);

        try {
            $reflectionClass = new ReflectionClass($entityClass);

            // Parcours des propriétés pour ajouter les paramètres d'index
            foreach ($reflectionClass->getProperties() as $property) {
                $attributes = $property->getAttributes(SearchProperty::class);
                if ($attributes) {
                    $annotation = $attributes[0]->newInstance();
                    $this->addIndexSettings($annotation);
                }
            }

            // Parcours des méthodes pour ajouter les paramètres d'index
            foreach ($reflectionClass->getMethods() as $method) {
                $attributes = $method->getAttributes(SearchProperty::class);
                if ($attributes) {
                    $annotation = $attributes[0]->newInstance();
                    $this->addIndexSettings($annotation);
                }
            }
        } catch (ReflectionException $e) {
            throw new Exception(\sprintf('Failed to inspect class %s: %s', $entityClass, $e->getMessage()));
        }

        // Mettre à jour les paramètres de l'index sur le client de recherche
        if ([] !== $this->indexSettings) {
            if (!isset($this->indexSettings['primaryKey'])) {
                throw new Exception('Primary key is required');
            }
            $this->client->updateSettings($indexName, $this->indexSettings);
        }

        // Réinitialiser les paramètres de l'index pour la prochaine utilisation
        $this->indexSettings = [];
    }

    private function handleProperties(ReflectionMethod|ReflectionProperty $property, object $entity, array &$data): void
    {
        $attributes = $property->getAttributes(SearchProperty::class);

        if ($attributes) {
            $property->setAccessible(true);
            $annotation = $attributes[0]->newInstance();
            if ($property instanceof ReflectionMethod) {
                $value = $property->invoke($entity);
            } else {
                $value = $property->getValue($entity);
            }

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
        }
    }

    private function addIndexSettings(SearchProperty $annotation): void
    {
        if ($annotation->filterable) {
            $this->indexSettings['filterable'][] = $annotation->propertyName;
        }

        if ($annotation->sortable) {
            $this->indexSettings['sortable'][] = $annotation->propertyName;
        }

        if ($annotation->searchable) {
            $this->indexSettings['searchable'][] = $annotation->propertyName;
        }
        if ($annotation->isPk) {
            $this->indexSettings['primaryKey'] = $annotation->propertyName;
        }
    }
}
