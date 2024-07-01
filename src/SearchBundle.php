<?php

namespace Nramos\SearchIndexer;

use Nramos\SearchIndexer\DependencyInjection\SearchBundleExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SearchBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SearchBundleExtension();
    }
}
