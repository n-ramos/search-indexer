<?php

namespace Nramos\SearchIndexer\Meilisearch\Filter;

use Nramos\SearchIndexer\Filter\SearchFilterInterface;

class MeiliSearchFilter implements SearchFilterInterface
{
    private array $filters = [];

    private array $separatorStack = [];

    public function addFilter(string $field, string $operator, mixed $value, string $separator = 'AND'): self
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
            'coordinates' => [$lat, $lng, $radius],
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
                    $value = '"'.$filter['value'].'"';
                    $filterStrings[] = sprintf('%s %s %s', $filter['field'], $filter['operator'], $value);

                    break;

                case 'in':
                    $values = '['.implode(', ', array_map(static fn ($val): string => '"'.$val.'"', $filter['values'])).']';
                    $filterStrings[] = sprintf('%s IN %s', $filter['field'], $values);

                    break;

                case 'location':
                    if ('radius' === $filter['location_type']) {
                        $filterStrings[] = sprintf(
                            '_geoRadius(%f, %f, %d%s)',
                            $filter['coordinates'][0],
                            $filter['coordinates'][1],
                            $filter['coordinates'][2],
                            $filter['unit']
                        );
                    } elseif ('bounding' === $filter['location_type']) {
                        $filterStrings[] = sprintf(
                            '_geoBoundingBox([%f, %f], [%f, %f])',
                            $filter['coordinates'][0],
                            $filter['coordinates'][1],
                            $filter['coordinates'][2],
                            $filter['coordinates'][3]
                        );
                    }

                    break;

                case 'existence':
                    $operator = $filter['exists'] ? 'EXISTS' : 'NOT EXISTS';
                    $filterStrings[] = sprintf('%s %s', $filter['field'], $operator);

                    break;

                case 'parenthesis':
                    $filterStrings[] = $filter['value'];

                    break;
            }
        }

        // Ajouter les séparateurs appropriés aux filtres
        $result = '';
        foreach ($filterStrings as $index => $filterString) {
            if ($index > 0) {
                $separator = $this->filters[$index - 1]['separator'];
                if ('' !== $result && !str_ends_with(trim($result), '(') && !str_starts_with(trim((string) $filterString), ')')) {
                    $result .= ' '.$separator.' ';
                }
            }

            $result .= $filterString;
        }

        return $result;
    }
}
