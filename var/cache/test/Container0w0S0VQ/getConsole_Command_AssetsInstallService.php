<?php

namespace Container0w0S0VQ;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class getConsole_Command_AssetsInstallService extends Nramos_SearchIndexer_Tests_TestKernelTestDebugContainer
{
    /**
     * Gets the private 'console.command.assets_install' shared service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'/vendor/symfony/console/Command/Command.php';
        include_once \dirname(__DIR__, 4).'/vendor/symfony/framework-bundle/Command/AssetsInstallCommand.php';
        include_once \dirname(__DIR__, 4).'/vendor/symfony/filesystem/Filesystem.php';

        $container->privates['console.command.assets_install'] = $instance = new \Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand(($container->privates['filesystem'] ??= new \Symfony\Component\Filesystem\Filesystem()), \dirname(__DIR__, 4));

        $instance->setName('assets:install');
        $instance->setDescription('Install bundle\'s web assets under a public directory');

        return $instance;
    }
}
