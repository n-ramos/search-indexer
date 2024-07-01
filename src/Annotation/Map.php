<?php

namespace Nramos\SearchIndexer\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Map
{
    public function __construct(
        public string $indexName,
        public bool $autoIndex = true
    ) {}
}
