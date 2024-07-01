<?php

namespace Nramos\SearchIndexer\Filter;

interface SearchFilterInterface
{
    public function addFilter(string $field, string $operator, mixed $value, string $separator = 'AND'): self;

    public function addInFilter(string $field, array $values, string $separator = 'AND'): self;

    public function addLocationFilter(string $type, float $lat, float $lng, int $radius = 5, string $unit = 'km', string $separator = 'AND'): self;

    public function addExistenceFilter(string $field, bool $exists = true, string $separator = 'AND'): self;

    public function openParenthesis(string $separator = 'AND'): self;

    public function closeParenthesis(): self;

    public function toString(): string;
}
