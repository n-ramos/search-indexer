<?php

namespace Nramos\SearchIndexer\DependencyInjection\Compiler;

use Nramos\SearchIndexer\Annotation\SearchIndex;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IndexableCompilerPass implements CompilerPassInterface
{
    /**
     * @throws \ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {

        // Initialisation d'un tableau pour stocker les noms de classes
        $indexedClasses = [];

        // Parcours des définitions de service
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            // Si la classe existe et qu'elle est chargée
            if ($class && class_exists($class, false)) {
                $reflectionClass = new ReflectionClass($class);

                // Vérifie si la classe possède l'attribut SearchIndex
                if (!empty($reflectionClass->getAttributes(SearchIndex::class))) {
                    $indexedClasses[] = $class; // Ajoute le nom de la classe
                }
            }
        }
        // Stocke la liste des noms de classes dans un paramètre du conteneur
        $container->setParameter('nramos.search_indexes', $indexedClasses);

    }

}