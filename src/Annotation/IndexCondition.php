<?php

namespace Nramos\SearchIndexer\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class IndexCondition
{
    public function __construct(
        public ?string $conditionClass = null
    ) {
    }
}