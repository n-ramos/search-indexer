<?php

namespace ContainerHH5Ex9k;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class getSearchIndexerSubscriberService extends Nramos_SearchIndexer_Tests_TestKernelTestDebugContainer
{
    /**
     * Gets the private 'Nramos\SearchIndexer\Indexer\SearchIndexerSubscriber' shared autowired service.
     *
     * @return \Nramos\SearchIndexer\Indexer\SearchIndexerSubscriber
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'/src/Indexer/SearchIndexerSubscriber.php';

        $a = ($container->privates['Nramos\\SearchIndexer\\Indexer\\GenericIndexer'] ?? $container->load('getGenericIndexerService'));

        if (isset($container->privates['Nramos\\SearchIndexer\\Indexer\\SearchIndexerSubscriber'])) {
            return $container->privates['Nramos\\SearchIndexer\\Indexer\\SearchIndexerSubscriber'];
        }

        return $container->privates['Nramos\\SearchIndexer\\Indexer\\SearchIndexerSubscriber'] = new \Nramos\SearchIndexer\Indexer\SearchIndexerSubscriber($a);
    }
}