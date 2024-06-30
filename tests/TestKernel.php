<?php

namespace Nramos\SearchIndexer\Tests;


use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            // Ajoutez d'autres bundles nécessaires pour vos tests
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes)
    {
        // Configuration des routes si nécessaire
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        // Chargement de la configuration du container si nécessaire
        $container->loadFromExtension('framework', [
            'secret' => 'S3cr3t',
            // Autres configurations Symfony si nécessaires
        ]);
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/logs';
    }
}
