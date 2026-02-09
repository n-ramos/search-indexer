<?php

namespace Nramos\SearchIndexer\Tests\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

trait EntityManagerInterfaceTrait
{
    use ConnectionTrait;
    private array $fixturesPath = [
        __DIR__.'/../Entity',
    ];

    private function createEntityManager(?array $paths = null, string $connectionName = 'default', ?array $params = null): EntityManagerInterface
    {
        $configuration = ORMSetup::createAttributeMetadataConfiguration($paths ?? $this->fixturesPath, true);
        if (method_exists($configuration, 'enableNativeLazyObjects')) {
            $configuration->enableNativeLazyObjects(true);
        } elseif (method_exists($configuration, 'setLazyGhostObjectEnabled')) {
            $configuration->setLazyGhostObjectEnabled(true);
        }
        $configuration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        $connection = $this->getConnection($connectionName, $params);

        $em = new EntityManager($connection, $configuration);
        $tool = new SchemaTool($em);
        $tool->updateSchema($em->getMetadataFactory()->getAllMetadata());

        return $em;
    }
}
