<?php

namespace Container0w0S0VQ;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class getDoctrine_Dbal_DefaultConnection_ConfigurationService extends Nramos_SearchIndexer_Tests_TestKernelTestDebugContainer
{
    /**
     * Gets the private 'doctrine.dbal.default_connection.configuration' shared service.
     *
     * @return \Doctrine\DBAL\Configuration
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'/vendor/doctrine/dbal/src/Configuration.php';
        include_once \dirname(__DIR__, 4).'/vendor/doctrine/dbal/src/Driver/Middleware.php';
        include_once \dirname(__DIR__, 4).'/vendor/doctrine/doctrine-bundle/src/Middleware/ConnectionNameAwareInterface.php';
        include_once \dirname(__DIR__, 4).'/vendor/doctrine/doctrine-bundle/src/Middleware/DebugMiddleware.php';
        include_once \dirname(__DIR__, 4).'/vendor/doctrine/dbal/src/Schema/SchemaManagerFactory.php';
        include_once \dirname(__DIR__, 4).'/vendor/doctrine/dbal/src/Schema/DefaultSchemaManagerFactory.php';
        include_once \dirname(__DIR__, 4).'/vendor/symfony/doctrine-bridge/Middleware/Debug/DebugDataHolder.php';
        include_once \dirname(__DIR__, 4).'/vendor/doctrine/doctrine-bundle/src/Middleware/BacktraceDebugDataHolder.php';

        $container->privates['doctrine.dbal.default_connection.configuration'] = $instance = new \Doctrine\DBAL\Configuration();

        $a = new \Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware(($container->privates['doctrine.debug_data_holder'] ??= new \Doctrine\Bundle\DoctrineBundle\Middleware\BacktraceDebugDataHolder([])), NULL);
        $a->setConnectionName('default');

        $instance->setSchemaManagerFactory(($container->privates['doctrine.dbal.default_schema_manager_factory'] ??= new \Doctrine\DBAL\Schema\DefaultSchemaManagerFactory()));
        $instance->setMiddlewares([$a]);

        return $instance;
    }
}
