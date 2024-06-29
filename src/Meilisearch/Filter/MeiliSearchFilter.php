<?php

namespace Nramos\SearchIndexer\Meilisearch\Filter;

use Nramos\SearchIndexer\Filter\SearchFilterInterface;

class MeiliSearchFilter implements SearchFilterInterface
{
    private $filters = [];
    private $separatorStack = [];

    public function addFilter(string $field, string $operator, $value, string $separator = 'AND'): self
    {
        $this->filters[] = [
            'type' => 'basic',
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'separator' => $separator,
        ];
        return $this;
    }

    public function addInFilter(string $field, array $values, string $separator = 'AND'): self
    {
        $this->filters[] = [
            'type' => 'in',
            'field' => $field,
            'values' => $values,
            'separator' => $separator,
        ];
        return $this;
    }

    public function addLocationFilter(string $type, float $lat, float $lng, int $radius = 5, string $unit = 'm', string $separator = 'AND'): self
    {
        $this->filters[] = [
            'type' => 'location',
            'location_type' => $type,
            'coordinates' => [$lat,$lng, $radius],
            'unit' => $unit,
            'separator' => $separator,
        ];
        return $this;
    }

    public function addExistenceFilter(string $field, bool $exists = true, string $separator = 'AND'): self
    {
        $this->filters[] = [
            'type' => 'existence',
            'field' => $field,
            'exists' => $exists,
            'separator' => $separator,
        ];
        return $this;
    }

    public function openParenthesis(string $separator = 'AND'): self
    {
        $this->filters[] = [
            'type' => 'parenthesis',
            'value' => '(',
            'separator' => $separator,
        ];
        $this->separatorStack[] = $separator;
        return $this;
    }

    public function closeParenthesis(): self
    {
        $separator = array_pop($this->separatorStack);
        $this->filters[] = [
            'type' => 'parenthesis',
            'value' => ')',
            'separator' => $separator,
        ];
        return $this;
    }

    public function toString(): string
    {
        $filterStrings = [];
        foreach ($this->filters as $filter) {
            switch ($filter['type']) {
                case 'basic':
                    $value = '"' . $filter['value'] . '"';
                    $filterStrings[] = sprintf('%s %s %s %s', $filter['field'], $filter['operator'], $value, $filter['separator']);
                    break;
                case 'in':
                    $values = '[' . implode(', ', array_map(function($val) {
                            return '"' . $val . '"';
                        }, $filter['values'])) . ']';
                    $filterStrings[] = sprintf('%s IN %s %s', $filter['field'], $values, $filter['separator']);
                    break;
                case 'location':
                    if ($filter['location_type'] === '_geoRadius') {
                        $filterStrings[] = sprintf('_geoRadius(%f, %f, %d%s) %s',
                            $filter['coordinates'][0], $filter['coordinates'][1], $filter['coordinates'][2], $filter['unit'], $filter['separator']);
                    } elseif ($filter['location_type'] === '_geoBoundingBox') {
                        $filterStrings[] = sprintf('_geoBoundingBox([%f, %f], [%f, %f]) %s',
                            $filter['coordinates'][0], $filter['coordinates'][1], $filter['coordinates'][2], $filter['coordinates'][3], $filter['separator']);
                    }
                    break;
                case 'existence':
                    $operator = $filter['exists'] ? 'EXISTS' : 'NOT EXISTS';
                    $filterStrings[] = sprintf('%s %s %s', $filter['field'], $operator, $filter['separator']);
                    break;
                case 'parenthesis':
                    $filterStrings[] = $filter['value'] . ' ';
                    break;
            }
        }

        // Ajouter les séparateurs appropriés aux filtres
        foreach ($filterStrings as $index => $filterString) {
            if ($index > 0 && !str_ends_with(trim($filterStrings[$index - 1]), '(') && !str_starts_with(trim($filterString), ')')) {
                $filterStrings[$index] = $this->filters[$index]['separator'] . ' ' . $filterString;
            }
        }

        return rtrim(implode(' ', $filterStrings), ' AND OR');
    }
}