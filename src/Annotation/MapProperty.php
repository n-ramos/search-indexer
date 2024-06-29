<?php

namespace Nramos\SearchIndexer\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MapProperty
{
    public function __construct(
        public string $propertyName,
        public array $relationProperties = [],
         public bool $filterable = false,
        public bool $sortable = false,
        public bool $searchable = true
    ) {
    }
}