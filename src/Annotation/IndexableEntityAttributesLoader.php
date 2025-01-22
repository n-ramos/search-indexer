<?php

namespace Nramos\SearchIndexer\Annotation;

use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;

class IndexableEntityAttributesLoader
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function load(): array
    {
        $indexedClasses = [];

        // Récupère les métadonnées des entités Doctrine
        $metaDataList = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metaDataList as $metaData) {
            $className = $metaData->getName();

            // Vérifie si la classe existe et possède l'attribut @SearchIndex
            if (class_exists($className)) {
                $reflectionClass = new ReflectionClass($className);
                if (!empty($reflectionClass->getAttributes(SearchIndex::class))) {
                    $indexedClasses[] = $className;
                }
            }
        }

        return $indexedClasses;
    }
}
