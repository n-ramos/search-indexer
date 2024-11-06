<?php

namespace Nramos\SearchIndexer\Tests\Traits;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Tools\DsnParser;
use Throwable;

trait ConnectionTrait
{
    /**
     * @var Connection[]
     */
    private static array $connections = [];

    /**
     * @throws Exception
     */
    private function getConnection(string $name = 'default', ?array $params = null): Connection
    {
        if (!isset(self::$connections[$name]) || !self::$connections[$name] instanceof Connection) {
            self::$connections[$name] = $this->createConnection($params);
        }

        return self::$connections[$name];
    }

    /**
     * @throws Exception
     */
    private function createConnection(?array $params = null): Connection
    {
        $params = self::getConnectionParameters($params);

        $config = new Configuration();

        $connection = DriverManager::getConnection($params, $config);
        $schemaManager = $connection->createSchemaManager();
        $schema = $schemaManager->introspectSchema();
        $stmts = $schema->toDropSql($connection->getDatabasePlatform());
        foreach ($stmts as $stmt) {
            $connection->executeStatement($stmt);
        }

        return DriverManager::getConnection($params, $config);
    }

    /**
     * @return array<string, string>
     */
    private static function getConnectionParameters(?array $params = null): array
    {
        if (null === $params && false === getenv('DATABASE_URL')) {
            // in memory SQLite DB
            return [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ];
        }

        if (null !== $params) {
            // provided params take precedence
            return $params;
        }

        // extract params from DATABASE_URL env variable
        $dsnParser = new DsnParser(['mysql' => 'pdo_mysql', 'pgsql' => 'pdo_pgsql', 'sqlite' => 'pdo_sqlite']);

        return $dsnParser->parse(getenv('DATABASE_URL'));
    }

    /**
     * @throws Exception
     */
    private function dropAndCreateDatabase(AbstractSchemaManager $schemaManager, string $dbname): void
    {
        try {
            $schemaManager->dropDatabase($dbname);
        } catch (Throwable) {
            // do nothing
        }

        $schemaManager->createDatabase($dbname);
    }
}
