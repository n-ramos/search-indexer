<?php

namespace Nramos\SearchIndexer\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class SearchProperty
{
    public function __construct(
        public string $propertyName,
        public array $relationProperties = [],
        public bool $isPk = false,
        public bool $filterable = false,
        public bool $sortable = false,
        public bool $searchable = true
    ) {}
}
