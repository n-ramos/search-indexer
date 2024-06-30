<?php

namespace ContainerHH5Ex9k;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class getFilesystemService extends Nramos_SearchIndexer_Tests_TestKernelTestDebugContainer
{
    /**
     * Gets the private 'filesystem' shared service.
     *
     * @return \Symfony\Component\Filesystem\Filesystem
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'/vendor/symfony/filesystem/Filesystem.php';

        return $container->privates['filesystem'] = new \Symfony\Component\Filesystem\Filesystem();
    }
}