<?php

namespace Container0w0S0VQ;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class getConsole_SuggestMissingPackageSubscriberService extends Nramos_SearchIndexer_Tests_TestKernelTestDebugContainer
{
    /**
     * Gets the private 'console.suggest_missing_package_subscriber' shared service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\EventListener\SuggestMissingPackageSubscriber
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'/vendor/symfony/framework-bundle/EventListener/SuggestMissingPackageSubscriber.php';

        return $container->privates['console.suggest_missing_package_subscriber'] = new \Symfony\Bundle\FrameworkBundle\EventListener\SuggestMissingPackageSubscriber();
    }
}
